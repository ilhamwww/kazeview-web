import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import os from 'os';

// Fix for www-data and restricted environment permissions
process.env.HOME = os.tmpdir();
process.env.XDG_CONFIG_HOME = os.tmpdir();
process.env.XDG_CACHE_HOME = os.tmpdir();

puppeteer.use(StealthPlugin());

const domain = process.argv[2];
const playInfoPath = process.argv[3];
const executablePath = process.argv[4];

if (!domain || !playInfoPath) {
    console.error('Error: Domain and Play Info Path are required.');
    process.exit(1);
}

(async () => {
    let browser;
    try {
        const launchOptions = {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled',
                '--disable-web-security',
                '--disable-dev-shm-usage',
                '--disable-gpu'
            ]
        };

        if (executablePath && executablePath !== 'undefined' && executablePath !== '') {
            launchOptions.executablePath = executablePath;
        }

        browser = await puppeteer.launch(launchOptions);

        const page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 800 });

        // Override default HeadlessChrome user agent
        const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
        await page.setUserAgent(userAgent);

        await page.setExtraHTTPHeaders({
            'Accept-Language': 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept': 'application/json, text/plain, */*'
        });

        // 1. Visit the main domain homepage to pass the Cloudflare challenge and get cookies
        await page.goto(domain, {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });

        // Check if CF challenge page is showing
        let title = await page.title();
        if (title.includes('Just a moment') || title.includes('Attention Required') || title.includes('Cloudflare')) {
            try {
                await page.waitForFunction(
                    () => !document.title.includes('Just a moment') && !document.title.includes('Cloudflare') && !document.title.includes('Attention Required'),
                    { timeout: 20000, polling: 1000 }
                );
            } catch (e) {
                // If it times out, we'll just try to continue
            }
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

                // Wait 16 seconds to bypass the unlock/claim countdown block
                await new Promise(resolve => setTimeout(resolve, 16000));

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