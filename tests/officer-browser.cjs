const assert = require('node:assert/strict');
const { spawn, execFileSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '..');
const php = process.env.PHP_BINARY || 'php';
const run = args => execFileSync(php, args, {cwd: root, encoding: 'utf8'});
(async () => {
 const fixture = JSON.parse(run(['tests/browser-fixture.php','officer']));
 const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'ecolot-browser-'));
 let server, browser;
 let output = '';
 try {
  const listener = require('node:net').createServer();
  await new Promise(resolve => listener.listen(0, '127.0.0.1', resolve));
  const port = listener.address().port;
  await new Promise(resolve => listener.close(resolve));
  const base = `http://127.0.0.1:${port}`;
  server = spawn(php, ['-d', `session.save_path=${temp}`, '-S', `127.0.0.1:${port}`, '-t', 'public', 'tests/browser-router.php'], {cwd: root, detached: true, env: {...process.env, PHP_CLI_SERVER_WORKERS: '4', DB_DATABASE: fixture.database, APP_BASE_PATH: '', APP_DEBUG: 'true'}});
  server.stderr.on('data', data => output += data);
  for (let i=0;i<60;i++) { try { if ((await fetch(`${base}/login`)).ok) break; } catch {} await new Promise(r=>setTimeout(r,100)); }
  browser = await chromium.launch({headless:true, executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'});
  const errors=[];
  const context = async () => {
   const c=await browser.newContext();
   await c.route('**/*', route => route.request().url().startsWith(base) ? route.continue() : route.abort());
   const page=await c.newPage(); page.setDefaultNavigationTimeout(60000); page.on('pageerror', e=>errors.push(e.message));
   return page;
  };
  const officer=await context(), user=await context();
  async function login(page, mobile) {
   await page.goto(`${base}/login`, {waitUntil:'commit'});
   await page.locator('[name=mobile_number]').fill(mobile);
   await page.locator('[name=password]').fill('BrowserTest123!');
   await Promise.all([page.waitForURL('**/dashboard', {waitUntil:'domcontentloaded'}),page.locator('button[type=submit]').click()]);
  }
  console.log('Checking officer login');
  await login(officer,'0771234567');

  for (const route of ['dashboard','campaigns','area-schedules','flagged-requests','collection-assignments']) {
   const response = await officer.goto(`${base}/officer/${route}`, {waitUntil:'domcontentloaded'});
   assert.equal(response.status(), 200, route);
   assert(!/Warning:|Fatal error:/.test(await officer.locator('body').innerText()), route);
   await officer.screenshot({path:`/tmp/ecolot-officer-${route}.png`,fullPage:true});
   await officer.setViewportSize({width:390,height:844});
   await officer.screenshot({path:`/tmp/ecolot-officer-${route}-mobile.png`,fullPage:true});
   assert(await officer.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), `${route}: page overflows on mobile`);
   await officer.screenshot({path:`/tmp/ecolot-officer-${route}-mobile.png`,fullPage:true});
   await officer.setViewportSize({width:1280,height:900});

  }
  await officer.goto(`${base}/officer/flagged-requests`);
  assert.match(await officer.locator('tbody').innerText(), /Cracked housing/);
  assert.equal((await officer.request.post(`${base}/officer/flagged-requests/1/review`, {form:{decision:'APPROVED',note:'Forged'}})).status(),403);
  await officer.getByText('Review request',{exact:true}).click();
  await officer.locator('[name=decision]').selectOption('APPROVED');
  await officer.locator('[name=note]').fill('Handle with protective equipment');
  await Promise.all([officer.waitForNavigation(),officer.getByRole('button',{name:'Save Review'}).click()]);
  await officer.reload();
  assert.match(await officer.locator('tbody').innerText(), /APPROVED/);
  assert.match(await officer.locator('tbody').innerText(), /Handle with protective equipment/);
  await officer.goto(`${base}/officer/area-schedules/1`);
  await officer.locator('[name=schedule_status]').selectOption('CLOSED');
  await Promise.all([officer.waitForNavigation(),officer.getByRole('button',{name:'Save Changes',exact:true}).click()]);
  await officer.goto(`${base}/officer/collection-assignments`);
  await officer.getByText('Assign resources',{exact:true}).click();
  await officer.locator('[name=collector]').selectOption('3');
  await officer.locator('[name=vehicle]').selectOption('1');
  await Promise.all([officer.waitForNavigation(),officer.getByRole('button',{name:'Assign',exact:true}).click()]);
  await officer.reload();
  assert.match(await officer.locator('tbody').innerText(), /ASSIGNED/);
  assert.match(await officer.locator('tbody').innerText(), /DEMO-01/);
  await officer.screenshot({path:'/tmp/ecolot-officer-assigned.png',fullPage:true});
  await officer.goto(`${base}/officer/area-schedules/1`);
  assert.equal(await officer.getByRole('button',{name:'Save Changes',exact:true}).count(),0);
  await officer.goto(`${base}/officer/collection-assignments`);
  officer.once('dialog', dialog => dialog.accept());
  await Promise.all([officer.waitForNavigation(),officer.getByRole('button',{name:'Unassign',exact:true}).click()]);
  assert.match(await officer.locator('tbody').innerText(), /CLOSED/);
  await officer.goto(`${base}/officer/campaigns`);
  await officer.getByRole('button',{name:'Create Campaign',exact:true}).first().click();

  await officer.locator('[name=period]').fill('2098-12');
  assert.equal(await officer.locator('[name=name]').inputValue(), 'December 2098 E-Waste Campaign');
  assert(await officer.locator('[name=name]').evaluate(el => el.readOnly));
  await Promise.all([officer.waitForNavigation(),officer.locator('[data-campaign-submit]').click()]);
  assert.match(await officer.locator('tbody').innerText(), /December 2098 E-Waste Campaign/);
  await officer.reload();
  assert.match(await officer.locator('tbody').innerText(), /December 2098 E-Waste Campaign/);
  await officer.locator('tr').filter({hasText:'December 2098 E-Waste Campaign'}).getByRole('button',{name:'Edit'}).click();
  await officer.locator('[name=status]').selectOption('CLOSED');
  await Promise.all([officer.waitForNavigation(),officer.locator('[data-campaign-submit]').click()]);
  assert.match(await officer.locator('tr').filter({hasText:'December 2098 E-Waste Campaign'}).innerText(), /CLOSED/);
  await officer.locator('.profile summary').click();
  assert.match(await officer.locator('.profile-details').innerText(), /Municipal Officer/);
  await Promise.all([officer.waitForURL('**/login'),officer.locator('.profile-details').getByRole('button',{name:'Logout',exact:true}).click()]);
  assert.equal(errors.length,0,errors.join('\n'));
  assert(!/PHP (Warning|Fatal)/.test(output), output);
  console.log('PASS: officer pages, risk review → schedule close → assign/unassign, CSRF, campaign persistence and profile/logout');
 } finally {
  await Promise.race([browser?.close(), new Promise(resolve => setTimeout(resolve, 3000))]);
  if(server) {process.kill(-server.pid, 'SIGTERM'); await new Promise(r=>server.once('exit',r));}
  run(['tests/browser-fixture.php','drop',fixture.database]);
  fs.rmSync(temp,{recursive:true,force:true});
 }
})().catch(error=>{console.error(error);process.exitCode=1});
