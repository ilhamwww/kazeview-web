import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import os from 'os';
import fs from 'fs';

// Fix for www-data and restricted environment permissions
process.env.TMPDIR = '/tmp';
process.env.HOME = '/tmp';
process.env.XDG_CONFIG_HOME = '/tmp';
process.env.XDG_CACHE_HOME = '/tmp';
process.env.XDG_DATA_HOME = '/tmp/.local/share';

puppeteer.use(StealthPlugin());

const domain = process.argv[2];
const playInfoPath = process.argv[3];
const executablePath = process.argv[4];

if (!domain || !playInfoPath) {
    console.error(JSON.stringify({ error: 'Domain and Play Info Path are required.' }));
    process.exit(1);
}

let chromePath = '/opt/puppeteer-chrome/chrome';
if (executablePath && executablePath !== 'undefined' && executablePath !== '' && fs.existsSync(executablePath) && executablePath !== '/usr/bin/google-chrome') {
    chromePath = executablePath;
} else if (fs.existsSync('/opt/puppeteer-chrome/chrome')) {
    chromePath = '/opt/puppeteer-chrome/chrome';
} else {
    chromePath = '/usr/bin/google-chrome';
}

(async () => {
    let browser;
    try {
        const launchOptions = {
            headless: 'new',
            executablePath: chromePath,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled',
                '--disable-web-security',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--no-zygote',
                '--single-process'
            ]
        };

        browser = await puppeteer.launch(launchOptions);

        const page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 800 });

        await page.setRequestInterception(true);
        page.on('request', (req) => {
            const rt = req.resourceType();
            if (['image', 'media', 'font'].includes(rt)) {
                req.abort();
            } else {
                req.continue();
            }
        });

        // Override default HeadlessChrome user agent
        const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
        await page.setUserAgent(userAgent);

        await page.setExtraHTTPHeaders({
            'Accept-Language': 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept': 'application/json, text/plain, */*'
        });

        // 1. Visit the main domain homepage to pass the Cloudflare challenge and get cookies
        try {
            await page.goto(domain, {
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
        } catch (e) {}

        // Poll until Cloudflare challenge passes
        for (let i = 0; i < 15; i++) {
            await new Promise(r => setTimeout(r, 500));
            try {
                const title = await page.title();
                if (title && !title.includes('Just a moment') && !title.includes('Cloudflare') && title.length > 0) {
                    break;
                }
            } catch (e) {}
        }

        // 2. Perform the API requests using page.evaluate to benefit from the browser session/cookies/bypass CORS
        const resultJson = await page.evaluate(async (playInfoPath) => {
            try {
                // Step 1: play-info
                const playInfoRes = await fetch(playInfoPath, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!playInfoRes.ok) {
                    throw new Error(`play-info failed with status ${playInfoRes.status}`);
                }
                const playInfo = await playInfoRes.json();
                const gateToken = playInfo.gateToken;
                if (!gateToken) {
                    throw new Error('No gateToken in play-info response');
                }

                // Dynamic wait based on unlockAt countdown from IDLIX API
                let waitMs = 15200;
                if (playInfo.unlockAt && playInfo.serverNow) {
                    waitMs = Math.max(0, playInfo.unlockAt - playInfo.serverNow) + 200;
                }
                await new Promise(resolve => setTimeout(resolve, waitMs));

                // Step 2: claim
                const claimRes = await fetch('/api/watch/session/claim', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ gateToken })
                });
                if (!claimRes.ok) {
                    throw new Error(`claim failed with status ${claimRes.status}`);
                }
                const claimData = await claimRes.json();
                const claim = claimData.claim;
                const redeemUrl = claimData.redeemUrl;
                if (!claim || !redeemUrl) {
                    throw new Error('No claim or redeemUrl in claim response');
                }

                // Step 3: redeem
                const redeemRes = await fetch(redeemUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ claim })
                });
                if (!redeemRes.ok) {
                    throw new Error(`redeem failed with status ${redeemRes.status}`);
                }
                const redeemData = await redeemRes.json();
                const configUrl = redeemData.url;

                // Step 4: JWPlayer config fetch (if needed)
                let videoUrl = configUrl;
                if (configUrl && !configUrl.includes('.m3u8')) {
                    try {
                        const configRes = await fetch(configUrl);
                        if (configRes.ok) {
                            const contentType = configRes.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                const configData = await configRes.json();
                                if (configData.playlist && configData.playlist[0]) {
                                    const playlistItem = configData.playlist[0];
                                    if (playlistItem.sources && playlistItem.sources.length > 0) {
                                        for (const src of playlistItem.sources) {
                                            if (src.file) {
                                                videoUrl = src.file;
                                                break;
                                            }
                                        }
                                    }
                                    if (!videoUrl && playlistItem.file) {
                                        videoUrl = playlistItem.file;
                                    }
                                }
                            }
                        }
                    } catch (err) {
                        // ignore and use configUrl
                    }
                }

                return {
                    video_url: videoUrl,
                    subtitles: redeemData.subtitles || [],
                    title: redeemData.title || ''
                };
            } catch (e) {
                return { error: e.message };
            }
        }, playInfoPath);

        process.stdout.write(JSON.stringify(resultJson));

    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
})();