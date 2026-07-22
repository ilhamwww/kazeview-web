import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import os from 'os';

// Fix for www-data and restricted environment permissions
process.env.HOME = os.tmpdir();
process.env.XDG_CONFIG_HOME = os.tmpdir();
process.env.XDG_CACHE_HOME = os.tmpdir();

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
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled',
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

        // Parse origin to navigate to base domain first if needed
        const urlObj = new URL(url);
        const origin = urlObj.origin;

        // Navigate directly to the requested URL
        await page.goto(url, {
            waitUntil: 'domcontentloaded',
            timeout: 30000
        });

        let title = await page.title();
        if (title.includes('Just a moment') || title.includes('Attention Required') || title.includes('Cloudflare')) {
            // Wait for Cloudflare challenge to pass
            try {
                await page.waitForFunction(
                    () => !document.title.includes('Just a moment') && !document.title.includes('Cloudflare') && !document.title.includes('Attention Required'),
                    { timeout: 20000, polling: 1000 }
                );
            } catch (e) {
                // If it times out, we'll just try to fetch anyway
            }
        }

        // Once challenge is passed (or if there was none), the cookies are set.
        // We use fetch inside evaluate to guarantee we get raw JSON without HTML wrappers.
        const jsonText = await page.evaluate(async (targetUrl) => {
            try {
                const res = await fetch(targetUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    return await res.text();
                }
            } catch (e) {
                // ignored
            }
            
            // Fallback to reading the body
            const pre = document.querySelector('pre');
            if (pre) return pre.innerText;
            return document.body.innerText;
        }, url);
        
        process.stdout.write(jsonText);

    } catch (error) {
        console.error('Puppeteer JSON scraping error:', error.message);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
})();