const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
let handler, calls = 0, callbacks;
const state = {}, attributes = {}, button = {}, output = {};
const form = {
    on: (_, fn) => { handler = fn; },
    data: (key, value) => { if (value === undefined) return state[key]; state[key] = value; return form; },
    attr: (key, value) => { if (value === undefined) return '/wp-admin/admin-ajax.php'; attributes[key] = value; return form; },
    removeAttr: key => { delete attributes[key]; return form; },
    find: selector => selector.startsWith('button') ? {prop: (key,value) => {button[key]=value;}} : {text: value => {output.text=value;}},
    serialize: () => 'action=cotizador_online&nonce=test'
};
function $(arg) { if (typeof arg === 'function') return arg($); return form; }
$.ajax = options => {
    calls++;
    assert.equal(options.type, 'POST'); assert.equal(options.timeout, 30000);
    callbacks = {};
    const chain = { done(fn) {callbacks.done=fn;return chain;}, fail(fn) {callbacks.fail=fn;return chain;}, always(fn) {callbacks.always=fn;return chain;} };
    return chain;
};
vm.runInNewContext(fs.readFileSync('js/scripts.js','utf8'), {jQuery:$});
const submit = () => handler.call(form,{preventDefault(){}});
submit(); submit();
assert.equal(calls,1); assert.equal(button.disabled,true);
callbacks.done({success:true,data:{message:'<img onerror=attack()>',total_price:'$21'}});
assert.equal(output.text,'<img onerror=attack()> Importe estimado: $21');
callbacks.always(); assert.equal(button.disabled,false); assert.equal(state.sending,false);
submit(); callbacks.fail({responseJSON:{data:{message:'Espera 10 minutos'}}},'error');
assert.equal(output.text,'Espera 10 minutos'); callbacks.always();
submit(); callbacks.fail({},'timeout'); assert.match(output.text,/podría haberse procesado/); callbacks.always();
submit(); callbacks.done(null); assert.match(output.text,/No pudimos/); callbacks.always();
assert.equal(calls,4);
console.log('Frontend: duplicate submit, text output, server errors, timeout and recovery passed');
