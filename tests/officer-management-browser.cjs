const assert = require('node:assert/strict');
const {spawn, execFileSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '..');
const php = process.env.PHP_BINARY || 'php';
const run = args => execFileSync(php, ['-d',`session.save_path=${os.tmpdir()}`,...args], {cwd:root,encoding:'utf8'});
(async () => {
    const fixture = JSON.parse(run(['tests/officer-management.php','--fixture']));
    const temp = fs.mkdtempSync(path.join(os.tmpdir(),'ecolot-management-browser-'));
    // Isolate runtime rate-limit files as well as the database and sessions.
    const appRoot=path.join(temp,'application'); fs.mkdirSync(appRoot);
    for (const directory of ['app','config','routes','public']) fs.cpSync(path.join(root,directory),path.join(appRoot,directory),{recursive:true});
    fs.mkdirSync(path.join(appRoot,'storage')); fs.mkdirSync(path.join(appRoot,'tests'));
    fs.copyFileSync(path.join(root,'tests/browser-router.php'),path.join(appRoot,'tests/browser-router.php'));
    let server, browser; let output='';
    try {
        const listener=require('node:net').createServer(); await new Promise(resolve=>listener.listen(0,'127.0.0.1',resolve));
        const port=listener.address().port; await new Promise(resolve=>listener.close(resolve)); const base=`http://127.0.0.1:${port}`;
        server=spawn(php,['-d',`session.save_path=${temp}`,'-S',`127.0.0.1:${port}`,'-t','public','tests/browser-router.php'],{cwd:appRoot,env:{...process.env,DB_DATABASE:fixture.database,APP_BASE_PATH:'',APP_DEBUG:'true'}});
        server.stderr.on('data',data=>output+=data);
        for(let i=0;i<60;i++) {try {if((await fetch(`${base}/login`)).ok) break;} catch {} await new Promise(r=>setTimeout(r,100));}
        browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'});
        const errors=[];
        async function pageFor(mobile) {
            const context=await browser.newContext(); await context.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
            const page=await context.newPage(); page.setDefaultNavigationTimeout(30000); page.on('pageerror',e=>errors.push(e.message)); page.on('dialog',async dialog=>{errors.push('Unexpected native dialog'); await dialog.dismiss();});
            await page.goto(`${base}/login`); await page.locator('[name=mobile_number]').fill(mobile); await page.locator('[name=password]').fill('BrowserTest123!');
            await Promise.all([page.waitForURL('**/dashboard'),page.locator('button[type=submit]').click()]); return page;
        }
        const page=await pageFor('0771234567');
        await page.getByRole('link',{name:'E-Lot Management',exact:true}).click();
        assert.match(await page.title(),/E-Lot Management/);
        assert.equal(await page.locator('.elots-table tbody tr').count(),20);
        await page.getByRole('link',{name:'Next',exact:true}).click(); assert.equal(await page.locator('.elots-table tbody tr').count(),3);
        await page.locator('#module-search').fill("%' OR 1=1 --");
        await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Apply Filters',exact:true}).click()]); assert.equal(await page.locator('.elots-table tbody tr').count(),0);
        await page.goto(`${base}/officer/e-lots?id=${fixture.primary}`);
        assert.match(await page.locator('#module-details').innerText(),/3.625/);
        assert.equal(await page.locator('#module-details .elots-table').first().locator('tbody tr').count(),2);
        const dateValue=offset=>new Date(Date.now()+offset).toLocaleString('sv-SE',{timeZone:'Asia/Colombo'}).replace(' ','T');
        await page.locator('#lot-open').fill(dateValue(3600000)); await page.locator('#lot-close').fill(dateValue(7200000)); await page.locator('#lot-note').fill('Reviewed <source>');
        let posts=0; page.on('request',request=>{if(request.method()==='POST'&&request.url().includes('/manage')) posts++;});
        const approve=page.locator('button[value=approve]'); await approve.click();
        assert(await page.locator('#officer-management-confirm').evaluate(el=>el.open));
        await page.locator('[data-management-cancel]').click(); assert.equal(posts,0); assert(await approve.evaluate(el=>document.activeElement===el));
        await approve.click(); await page.keyboard.press('Escape'); assert.equal(posts,0);
        await page.setViewportSize({width:390,height:844}); assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'E-Lot mobile overflow');
        await approve.click(); await page.screenshot({path:'/tmp/ecolot-management-confirm-mobile.png',fullPage:true});
        await Promise.all([page.waitForNavigation(),page.locator('[data-management-confirm]').click()]); assert.equal(posts,1);
        await page.reload(); assert.match(await page.locator('#module-details').innerText(),/Bidding scheduled/); assert.match(await page.locator('.module-history').innerText(),/Reviewed <source>/);
        assert.equal((await page.request.post(`${base}/officer/e-lots/${fixture.primary}/manage`,{form:{action:'reject',note:'Forged'}})).status(),403);
        await page.goto(`${base}/officer/e-lots?id=${fixture.second}`); await page.locator('#lot-note').fill('   '); await page.locator('button[value=reject]').click(); assert.equal(posts,1);
        await page.locator('#lot-note').fill('Correct <description>'); await page.locator('button[value=reject]').click();
        await Promise.all([page.waitForNavigation(),page.locator('[data-management-confirm]').click()]); await page.reload(); assert.match(await page.locator('#module-details').innerText(),/Correct <description>/);
        await page.goto(`${base}/officer/e-lots?id=${fixture.ready}`);
        const winningForm=page.locator(`form:has(input[name=bid_id][value="${fixture.bid}"])`);
        await winningForm.getByRole('button',{name:'Select Winner',exact:true}).click();
        await Promise.all([page.waitForNavigation(),page.locator('[data-management-confirm]').click()]);
        await page.reload(); assert.match(await page.locator('#module-details').innerText(),/Digital handover/);
        await page.locator('#handover-status').selectOption('COMPLETED'); await page.locator('#handover-date').fill(dateValue(0));
        await page.locator('#handover-note').fill('Browser delivery confirmed');
        await page.getByRole('button',{name:'Save Handover',exact:true}).click();
        await Promise.all([page.waitForNavigation(),page.locator('[data-management-confirm]').click()]);
        await page.reload(); assert.match(await page.locator('#module-details').innerText(),/Browser delivery confirmed/);
        assert.equal(await page.getByRole('button',{name:'Save Handover',exact:true}).count(),0);
        await page.setViewportSize({width:1280,height:900});
        await page.getByRole('link',{name:'Feedback Management',exact:true}).click(); assert.match(await page.title(),/Feedback Management/);
        assert.equal(await page.locator('.feedback-table tbody tr').count(),20); await page.getByRole('link',{name:'Next',exact:true}).click(); assert.equal(await page.locator('.feedback-table tbody tr').count(),5);
        await page.goto(`${base}/officer/feedback?id=1`); assert.match(await page.locator('#module-details').innerText(),/<script>alert\(1\)<\/script>/);
        await page.goto(`${base}/officer/feedback?id=26`);
        await page.locator('#feedback-status').selectOption('RESOLVED'); await page.locator('#feedback-response').fill('   '); await page.getByRole('button',{name:'Save Update',exact:true}).click(); assert(!(await page.locator('#officer-management-confirm').evaluate(el=>el.open)));
        await page.locator('#feedback-response').fill('Resolved <safely>'); await page.getByRole('button',{name:'Save Update',exact:true}).click();
        await Promise.all([page.waitForNavigation(),page.locator('[data-management-confirm]').click()]); await page.reload(); assert.match(await page.locator('.module-history').innerText(),/Resolved <safely>/);
        await page.locator('#feedback-status').selectOption('CLOSED'); await page.locator('#feedback-response').fill('Final response'); await page.getByRole('button',{name:'Save Update',exact:true}).click();
        await Promise.all([page.waitForNavigation(),page.locator('[data-management-confirm]').click()]); await page.reload(); assert.equal(await page.getByRole('button',{name:'Save Update',exact:true}).count(),0); assert.match(await page.locator('.module-history').innerText(),/Resolved <safely>/);
        await page.setViewportSize({width:390,height:844}); assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Feedback mobile overflow'); await page.screenshot({path:'/tmp/ecolot-feedback-history-mobile.png',fullPage:true});
        const other=await pageFor('0771234570'); assert.equal((await other.goto(`${base}/officer/e-lots?id=${fixture.primary}`)).status(),404); assert.equal((await other.goto(`${base}/officer/feedback?id=26`)).status(),404);
        const publicPage=await pageFor('0771234568'); assert.equal((await publicPage.goto(`${base}/user/feedback`)).status(),200); assert.match(await publicPage.locator('body').innerText(),/Final response/);
        assert.equal(errors.length,0,errors.join('\n')); assert(!/PHP (Warning|Fatal)/.test(output),output);
        console.log('PASS: Chrome Officer E-Lot/Feedback lists, search, pagination, source totals, custom confirmation cancel/Escape/focus, persisted review/award/handover/feedback/history, scope, CSRF and responsive layout');
    } finally {
        await browser?.close(); if(server) {server.kill(); await new Promise(resolve=>server.once('exit',resolve));}
        run(['tests/officer-management.php','--drop',fixture.database]); fs.rmSync(temp,{recursive:true,force:true});
    }
})().catch(error=>{console.error(error);process.exitCode=1});
