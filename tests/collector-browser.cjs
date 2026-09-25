const assert = require('node:assert/strict');
const {spawn, execFileSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '..');
const php = process.env.PHP_BINARY || 'php';
const run = args => execFileSync(php, ['-d', `session.save_path=${os.tmpdir()}`, ...args], {cwd:root, encoding:'utf8'});
(async () => {
    const fixture = JSON.parse(run(['tests/collector-crud.php','--fixture']));
    const temp = fs.mkdtempSync(path.join(os.tmpdir(),'ecolot-collector-browser-'));
    let server, browser;
    let output = '';
    try {
        const listener = require('node:net').createServer();
        await new Promise(resolve=>listener.listen(0,'127.0.0.1',resolve));
        const port = listener.address().port;
        await new Promise(resolve=>listener.close(resolve));
        const base = `http://127.0.0.1:${port}`;
        server=spawn(php,['-d',`session.save_path=${temp}`,'-S',`127.0.0.1:${port}`,'-t','public','tests/browser-router.php'],
            {cwd:root,env:{...process.env,DB_DATABASE:fixture.database,APP_BASE_PATH:'',APP_DEBUG:'true'}});
        server.stderr.on('data', data=>output+=data);
        for(let i=0;i<60;i++) {try {if((await fetch(`${base}/login`)).ok) break;} catch {} await new Promise(r=>setTimeout(r,100));}
        browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'});
        const context=await browser.newContext();
        await context.route('https://**',route=>route.abort());
        const page=await context.newPage();
        const errors=[]; page.on('pageerror',e=>errors.push(e.message));
        page.on('dialog',dialog=>dialog.accept());
        await page.goto(`${base}/login`);
        await page.locator('[name=mobile_number]').fill('0771234569');
        await page.locator('[name=password]').fill('BrowserTest123!');
        await Promise.all([page.waitForURL('**/dashboard'),page.locator('button[type=submit]').click()]);
        await page.getByRole('link',{name:'Assigned Requests',exact:true}).click();
        assert.equal(await page.locator('tbody tr').count(),2);
        const foreign=await context.request.get(`${base}/collector/my-requests/3`); assert.equal(foreign.status(),404);
        await page.getByRole('link',{name:'Record Collection',exact:true}).first().click();
        async function fill(quantity,weight,result) {
            await page.locator('[name="items[1][actual_quantity]"]').fill(quantity);
            await page.locator('[name="items[1][actual_weight_kg]"]').fill(weight);
            await page.locator('[name="items[1][actual_condition]"]').selectOption('DAMAGED');
            await page.locator('[name=pickup_result]').selectOption(result);
        }
        const click=async name=>Promise.all([page.waitForNavigation(),page.getByRole('button',{name,exact:true}).click()]);
        await fill('2','1.250','COLLECTED'); await click('Save Draft');
        const recordURL=page.url();
        await page.reload(); assert.equal(await page.locator('[name="items[1][actual_quantity]"]').inputValue(),'2');
        await fill('1','0.500','PARTIAL'); await click('Save Changes');
        await page.reload(); assert.equal(await page.locator('[name="items[1][actual_quantity]"]').inputValue(),'1');
        await page.screenshot({path:'/tmp/ecolot-collector-draft.png',fullPage:true});
        await click('Delete');
        assert.equal((await context.request.get(recordURL)).status(),404);
        await page.getByRole('link',{name:'Record Collection',exact:true}).first().click();
        await fill('2','1.250','COLLECTED'); await click('Save Draft');
        const savedURL=page.url();
        await page.getByRole('link',{name:'Back to Assigned Requests'}).click();
        await page.getByRole('link',{name:'Record Collection',exact:true}).click();
        await page.locator('[name="items[2][actual_quantity]"]').fill('0');
        await page.locator('[name=pickup_result]').selectOption('NOT_COLLECTED');
        await click('Save Draft');
        await click('Submit Schedule for Verification');
        assert.match(await page.locator('[role=status]').innerText(),/read-only/);
        await page.goto(savedURL); await page.reload();
        assert.equal(await page.getByRole('button',{name:'Save Changes',exact:true}).count(),0);
        assert.equal(await page.getByRole('button',{name:'Delete',exact:true}).count(),0);
        assert(await page.locator('[name=pickup_result]').isDisabled());
        const token=await page.locator('[name=_csrf_token]').first().inputValue();
        const badCSRF=await context.request.post(`${savedURL}/delete`,{form:{_csrf_token:'bad'}}); assert.equal(badCSRF.status(),403);
        await context.request.post(`${savedURL}/delete`,{form:{_csrf_token:token},maxRedirects:0});
        await page.goto(savedURL); assert.match(await page.locator('[role=alert]').innerText(),/not open|read-only/);
        assert.match(await page.locator('.collector-page-heading h1').innerText(),/Collection Record/);
        await page.setViewportSize({width:390,height:844});
        await page.screenshot({path:'/tmp/ecolot-collector-submitted-mobile.png',fullPage:true});
        assert.equal(await page.evaluate(()=>localStorage.length),0);
        assert.deepEqual(errors,[]);
        assert(!/PHP (Warning|Fatal|Notice)/.test(output),output);
        console.log('PASS: Collector browser login, navigation, MySQL persistence after reload, create/edit/delete, schedule submit, read-only, forged request, CSRF, mobile rendering and no localStorage');
    } finally {
        if(browser) await browser.close();
        if(server) {server.kill(); await new Promise(resolve=>server.once('exit',resolve));}
        run(['tests/collector-crud.php','--drop',fixture.database]);
        fs.rmSync(temp,{recursive:true,force:true});
    }
})().catch(error=>{console.error(error);process.exitCode=1;});
