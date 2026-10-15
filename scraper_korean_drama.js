import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

process.env.TMPDIR = '/tmp';
process.env.HOME = '/tmp';
process.env.XDG_CONFIG_HOME = '/tmp';
process.env.XDG_CACHE_HOME = '/tmp';
process.env.XDG_DATA_HOME = '/tmp/.local/share';

puppeteer.use(StealthPlugin());

const arg1 = process.argv[2] || '1';
let apiUrl;
let domain = 'https://z2.idlixku.com';

if (arg1.startsWith('http://') || arg1.startsWith('https://')) {
    apiUrl = arg1;
    try {
        const u = new URL(apiUrl);
        domain = u.origin;
    } catch (e) {}
} else {
    const pageNum = arg1;
    const limit = process.argv[3] || '36';
    const sort = process.argv[4] || 'latest';
    if (process.argv[5] && process.argv[5].startsWith('http')) {
        domain = process.argv[5].replace(/\/+$/, '');
    }
    apiUrl = `${domain}/api/browse?page=${pageNum}&limit=${limit}&sort=${sort}&genre=drama&country=KR&language=ko`;
}

let chromePath = '/opt/puppeteer-chrome/chrome';
if (!fs.existsSync(chromePath)) {
    chromePath = '/usr/bin/google-chrome';
}

(async () => {
    let browser;
    try {
        browser = await puppeteer.launch({
            headless: 'new',
            executablePath: chromePath,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--no-zygote',
                '--single-process'
            ]
        });

        const page = await browser.newPage();
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        await page.setViewport({ width: 1280, height: 800 });

        const landingUrl = `${domain}/korean-drama`;
        try {
            await page.goto(landingUrl, { waitUntil: 'domcontentloaded', timeout: 10000 });
        } catch (e) {}

        // Wait until Cloudflare challenge completes and page title reflects real page
        for (let i = 0; i < 10; i++) {
            await new Promise(r => setTimeout(r, 500));
            try {
                const title = await page.title();
                if (title && !title.includes('Just a moment') && !title.includes('Cloudflare') && title.length > 0) {
                    break;
                }
            } catch (e) {}
        }

        let data = null;
        for (let attempt = 0; attempt < 5; attempt++) {
            try {
                data = await page.evaluate(async (url) => {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json, text/plain, */*'
                        }
                    });
                    if (res.ok) {
                        return await res.json();
                    }
                    return null;
                }, apiUrl);
                if (data && (data.data || data.above)) {
                    break;
                }
            } catch (e) {
                await new Promise(r => setTimeout(r, 1000));
            }
        }

        if (!data) {
            throw new Error('Failed to retrieve Korean drama data after retries');
        }

        process.stdout.write(JSON.stringify(data));
    } catch (err) {
        console.error('Error fetching Korean drama:', err.message);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
})();
