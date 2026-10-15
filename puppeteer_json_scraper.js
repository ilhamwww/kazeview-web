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

const url = process.argv[2];
const executablePath = process.argv[3];

if (!url) {
    console.error('Error: URL parameter is required.');
    process.exit(1);
}

(async () => {
    let browser;
    try {
        const launchOptions = {
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--no-zygote',
                '--single-process'
            ]
        };

        let chromePath = '/opt/puppeteer-chrome/chrome';
        if (executablePath && executablePath !== 'undefined' && executablePath !== '' && fs.existsSync(executablePath) && executablePath !== '/usr/bin/google-chrome') {
            chromePath = executablePath;
        } else if (fs.existsSync('/opt/puppeteer-chrome/chrome')) {
            chromePath = '/opt/puppeteer-chrome/chrome';
        } else {
            chromePath = '/usr/bin/google-chrome';
        }
        launchOptions.executablePath = chromePath;

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

        // Parse origin to navigate to base domain first if needed
        const urlObj = new URL(url);
        const origin = urlObj.origin;
        const initialNavUrl = url.includes('/api/') ? `${origin}/korean-drama` : url;

        // Navigate to get clearance cookies
        try {
            await page.goto(initialNavUrl, {
                waitUntil: 'domcontentloaded',
                timeout: 25000
            });
        } catch (navErr) {
            // Proceed even if full domcontentloaded timed out
        }

        // Wait for page to be settled (title not Just a moment...)
        for (let i = 0; i < 15; i++) {
            await new Promise(r => setTimeout(r, 1000));
            try {
                const title = await page.title();
                if (title && !title.includes('Just a moment') && !title.includes('Cloudflare')) {
                    break;
                }
            } catch (e) {}
        }

        // Fetch target JSON inside page context with retry in case frame navigates
        let jsonText = null;
        for (let attempt = 0; attempt < 6; attempt++) {
            try {
                jsonText = await page.evaluate(async (targetUrl) => {
                    const res = await fetch(targetUrl, {
                        headers: { 'Accept': 'application/json, text/plain, */*' }
                    });
                    if (res.ok) {
                        return await res.text();
                    }
                    return null;
                }, url);
                if (jsonText) break;
            } catch (e) {
                await new Promise(r => setTimeout(r, 1000));
            }
        }

        if (!jsonText) {
            try {
                jsonText = await page.evaluate(() => {
                    const pre = document.querySelector('pre');
                    if (pre) return pre.innerText;
                    return document.body.innerText;
                });
            } catch (e) {}
        }
        
        process.stdout.write(jsonText || '');

    } catch (error) {
        console.error('Puppeteer JSON scraping error:', error.message);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
})();