const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
for (const [file, name] of [['pickup-request-form', 'escapeHtml'], ['pickup-request-history', 'esc']]) {
    const source = fs.readFileSync(`${__dirname}/../public/assets/js/public_user/${file}.js`, 'utf8');
    const helper = source.match(new RegExp(`function ${name}\\(str\\) \\{[\\s\\S]*?\\n\\}`))[0];
    const escape = vm.runInNewContext(`(${helper})`);
    assert.equal(escape('" onfocus="alert(1)'), '&quot; onfocus=&quot;alert(1)');
    assert.equal(escape('<img src=x onerror=alert(1)>'), '&lt;img src=x onerror=alert(1)&gt;');
    assert.equal(escape("A&B's"), 'A&amp;B&#39;s');
    assert.equal(escape('Normal note'), 'Normal note');
}
console.log('PASS: HTML text and attribute escaping');
