// "Ingliz tili" bo'limi: kartochkalar, o'rganish, test va juftlash. Ma'lumotlar /english/sets API orqali saqlanadi.
(() => {
'use strict';
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const uid = () => Math.random().toString(36).slice(2, 10);
const shuffle = a => { a = [...a]; for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
const norm = s => String(s).toLowerCase().replace(/[ʻʼ‘’`´]/g, "'").replace(/[.,!?;:"()]/g, ' ').replace(/\s+/g, ' ').trim();
// A typed answer is right if it matches the whole definition or any alternative separated by ; / ,
const isRight = (given, answer) => { const g = norm(given); return !!g && (g === norm(answer) || answer.split(/[;\/,]/).map(norm).includes(g)); };

let S = { sets: [] };

/* ---------- server ---------- */
const root = document.getElementById('english-app');
const SETS = root.dataset.setsUrl;
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
async function api(method, url, body) {
  const json = body ? JSON.stringify(body) : undefined;
  let res;
  // keepalive lets a progress save finish while the page closes; browsers cap such bodies at 64 KB.
  try { res = await fetch(url, { method, body: json, credentials: 'same-origin', keepalive: method === 'PUT' && json.length < 30000,
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', ...(json ? { 'Content-Type': 'application/json' } : {}) } }); }
  catch (e) { throw new Error("Serverga ulanib bo'lmadi. Internetni tekshiring."); }
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const first = data.errors && Object.values(data.errors)[0];
    const msg = res.status === 401 || res.status === 419 ? "Sessiya muddati tugadi. Sahifani yangilab, qayta kiring."
      : res.status === 403 ? "Bu bo'limga ruxsatingiz yo'q."
      : (first && first[0]) || 'Xatolik yuz berdi. Qayta urinib ko\'ring.';
    const err = new Error(msg); err.status = res.status; throw err;
  }
  return data;
}
function toast(msg) { const t = $('#english-toast'); t.textContent = msg; t.hidden = false; clearTimeout(toast.t); toast.t = setTimeout(() => t.hidden = true, 3500); }
// Study progress is written in the background: changes to one set are batched and sent in order.
const pending = new Map();
function save(s) {
  if (pending.has(s.id)) return;
  pending.set(s.id, setTimeout(() => flush(s), 400));
}
function flush(s) {
  clearTimeout(pending.get(s.id)); pending.delete(s.id);
  s.saving = (s.saving || Promise.resolve()).then(() => api('PUT', SETS + '/' + s.id, { title: s.title, desc: s.desc, cards: s.cards, best: s.best }))
    .catch(e => toast("O'zgarishlar saqlanmadi. " + e.message));
}
addEventListener('pagehide', () => { for (const id of [...pending.keys()]) { const s = get(id); if (s) flush(s); } });
const get = id => S.sets.find(s => s.id === id);
const mastered = s => s.cards.filter(c => (c.lvl || 0) >= 2).length;

const app = root;
let acts = {}, keyFn = null, timers = [];
function show(html, a, k) { app.innerHTML = html; acts = { open: b => go('#/set/' + b.dataset.id), home: () => go('#/'), ...a }; keyFn = k || null; }
function nav(fn, ...args) { timers.forEach(t => { clearTimeout(t); clearInterval(t); }); timers = []; fn(...args); window.scrollTo(0, 0); }
app.addEventListener('click', e => { const b = e.target.closest('[data-act]'); if (b && acts[b.dataset.act]) acts[b.dataset.act](b, e); });
document.addEventListener('keydown', e => { if (/INPUT|TEXTAREA/.test(e.target.tagName)) return; if (keyFn) keyFn(e); });

/* ---------- Set kinds: plain words, or verbs with their three forms ---------- */
const isVerbs = s => s.kind === 'verbs';
// [card key, short label, placeholder]
const FIELDS = {
  words: [['term', 'Atama', "Yangi so'z"], ['def', "Ta'rif", "Tarjimasi yoki ta'rifi"]],
  verbs: [['term', 'V1', 'V1: go'], ['v2', 'V2', 'V2: went'], ['v3', 'V3', 'V3: gone'], ['def', 'Tarjima', 'Tarjimasi']],
};
const fieldsOf = kind => FIELDS[kind === 'verbs' ? 'verbs' : 'words'];
const complete = (c, kind) => fieldsOf(kind).every(([k]) => c[k]);
const sameCard = (a, b, kind) => fieldsOf(kind).every(([k]) => norm(a[k]) === norm(b[k]));

// The common irregular verbs: "V1 V2 V3". Anything else is treated as regular (+ed).
const IRREGULAR = Object.fromEntries(`be was/were been|bear bore born|beat beat beaten|become became become|begin began begun|bend bent bent|bet bet bet|bind bound bound|bite bit bitten|bleed bled bled|blow blew blown|break broke broken|breed bred bred|bring brought brought|build built built|burn burnt burnt|buy bought bought|catch caught caught|choose chose chosen|come came come|cost cost cost|creep crept crept|cut cut cut|deal dealt dealt|dig dug dug|do did done|draw drew drawn|dream dreamt dreamt|drink drank drunk|drive drove driven|eat ate eaten|fall fell fallen|feed fed fed|feel felt felt|fight fought fought|find found found|flee fled fled|fly flew flown|forbid forbade forbidden|forget forgot forgotten|forgive forgave forgiven|freeze froze frozen|get got got|give gave given|go went gone|grow grew grown|hang hung hung|have had had|hear heard heard|hide hid hidden|hit hit hit|hold held held|hurt hurt hurt|keep kept kept|kneel knelt knelt|know knew known|lay laid laid|lead led led|leave left left|lend lent lent|let let let|lie lay lain|light lit lit|lose lost lost|make made made|mean meant meant|meet met met|pay paid paid|put put put|quit quit quit|read read read|ride rode ridden|ring rang rung|rise rose risen|run ran run|say said said|see saw seen|seek sought sought|sell sold sold|send sent sent|set set set|shake shook shaken|shine shone shone|shoot shot shot|show showed shown|shrink shrank shrunk|shut shut shut|sing sang sung|sink sank sunk|sit sat sat|sleep slept slept|slide slid slid|speak spoke spoken|spend spent spent|split split split|spread spread spread|spring sprang sprung|stand stood stood|steal stole stolen|stick stuck stuck|sting stung stung|strike struck struck|swear swore sworn|sweep swept swept|swim swam swum|swing swung swung|take took taken|teach taught taught|tear tore torn|tell told told|think thought thought|throw threw thrown|understand understood understood|wake woke woken|wear wore worn|weep wept wept|win won won|wind wound wound|write wrote written`
  .split('|').map(v => { const [a, b, c] = v.split(' '); return [a, [b, c]]; }));
// Suggested V2 and V3 for a base form; phrasal verbs ("get up") conjugate the first word.
function verbForms(v1) {
  const [head, ...rest] = v1.trim().toLowerCase().split(/\s+/);
  if (!/^[a-z]+$/.test(head || '')) return null;
  let forms = IRREGULAR[head];
  if (!forms) {
    const ed = head.endsWith('e') ? head + 'd'
      : /[^aeiou]y$/.test(head) ? head.slice(0, -1) + 'ied'
      : /^[^aeiou]*[aeiou][^aeiouwxy]$/.test(head) ? head + head.slice(-1) + 'ed'   // stop → stopped
      : head + 'ed';
    forms = [ed, ed];
  }
  const tail = rest.length ? ' ' + rest.join(' ') : '';
  return forms.map(f => f + tail);
}
// One pasted or typed line → a card, or null. Words: "apple - olma". Verbs: "go - went - gone - bormoq" (also "go went gone - bormoq").
function parseLine(line, kind) {
  if (kind !== 'verbs') {
    const sep = ['\t', ' - ', ' – ', ' — ', '=', ';', ':', ',', '-'].find(x => line.includes(x));
    if (!sep) return null;
    const i = line.indexOf(sep), term = line.slice(0, i).trim(), def = line.slice(i + sep.length).trim();
    return term && def ? { term, def } : null;
  }
  for (const re of [/\t+/, /\s+[-–—]\s+/, /\s*[;,]\s*/]) {
    let parts = line.split(re).map(x => x.trim()).filter(Boolean);
    if (parts.length === 2) { const f = parts[0].split(/[\s,\/]+/); if (f.length === 3) parts = [...f, parts[1]]; }
    if (parts.length >= 4) return { term: parts[0], v2: parts[1], v3: parts[2], def: parts.slice(3).join(', ') };
  }
  return null;
}
const fieldInput = ([k, label, hint], value, usePlaceholder) =>
  `<input data-k="${k}" placeholder="${esc(usePlaceholder ? hint : label)}" aria-label="${esc(label)}" value="${esc(value)}" autocomplete="off"${k === 'def' ? '' : ' autocapitalize="off" spellcheck="false"'}>`;
// In a verb row, leaving V1 fills empty V2 and V3 with a suggestion.
function suggestForms(rowEl) {
  const get = k => rowEl.querySelector(`[data-k="${k}"]`);
  if (!get('v2') || !get('term').value.trim() || get('v2').value.trim() || get('v3').value.trim()) return;
  const f = verbForms(get('term').value);
  if (f) { get('v2').value = f[0]; get('v3').value = f[1]; }
}

/* ---------- Library ---------- */
function home() {
  const cards = list => list.length ? `<div class="grid">${list.map(s => `
    <button class="set" data-act="open" data-id="${s.id}">
      <b>${esc(s.title)}</b>${isVerbs(s) ? `<span class="lvl l1" style="align-self:flex-start">Fe'llar</span>` : ''}<span class="grow"></span>
      <span class="small muted">${s.cards.length} ta atama · ${mastered(s)} tasi o'zlashtirilgan</span>
      <span class="bar"><i style="width:${Math.round(mastered(s) / s.cards.length * 100)}%"></i></span>
    </button>`).join('')}</div>` : `<p class="empty muted">Hech narsa topilmadi.</p>`;
  show(`<div class="head"><div><h1>Kutubxona</h1><p class="muted">${S.sets.length} ta to'plam</p></div>
      <button class="btn primary" data-act="new">+ Yangi to'plam</button></div>
    ${S.sets.length > 4 ? `<p style="margin-bottom:14px"><input id="q" type="search" placeholder="To'plamlarni qidirish" aria-label="Qidirish"></p>` : ''}
    <div id="list">${S.sets.length ? cards(S.sets) : `<div class="panel empty"><h2>Hali to'plam yo'q</h2><p class="muted" style="margin:8px 0 16px">Birinchi to'plamingizni yarating va yodlashni boshlang.</p><button class="btn primary" data-act="new">To'plam yaratish</button></div>`}</div>`,
    { new: () => nav(editor) });
  const q = $('#q');
  if (q) q.oninput = () => { $('#list').innerHTML = cards(S.sets.filter(s => s.title.toLowerCase().includes(q.value.trim().toLowerCase()))); };
}

/* ---------- PDF: the generator and its library are loaded on first use ---------- */
const ASSETS = document.currentScript.src.replace(/[^/]*$/, '');
let pdfReady;
function loadPdf() {
  const script = src => new Promise((ok, fail) => { const el = document.createElement('script'); el.src = ASSETS + src; el.onload = ok; el.onerror = fail; document.head.append(el); });
  return pdfReady ||= script('vendor/jspdf.umd.min.js').then(() => script('english-pdf.js')).catch(e => { pdfReady = null; throw e; });
}

/* ---------- Set page ---------- */
const LVL = ['Yangi', "O'rganilmoqda", "O'zlashtirildi"];
const ICON = {
  edit: '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.5 3.5l3 3L7 16H4v-3z"/><path d="M11.5 5.5l3 3"/></svg>',
  up: '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5-5 5 5"/></svg>',
  down: '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8l5 5 5-5"/></svg>',
  grip: '<svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor" aria-hidden="true"><circle cx="7" cy="5" r="1.4"/><circle cx="13" cy="5" r="1.4"/><circle cx="7" cy="10" r="1.4"/><circle cx="13" cy="10" r="1.4"/><circle cx="7" cy="15" r="1.4"/><circle cx="13" cy="15" r="1.4"/></svg>',
  trash: '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h12M8 6V4h4v2M6 6l.7 10h6.6L14 6"/></svg>',
};
function setView(id) {
  const s = get(id); if (!s) return home();
  const verbs = isVerbs(s), fields = fieldsOf(s.kind);
  // q: search text; editing / removing: id of the row in that state; fresh: id of the row just added
  let q = '', editing = null, removing = null, fresh = null;
  const rowId = b => b.closest('.term').dataset.id;
  const card = cid => s.cards.find(c => c.id === cid);
  const cells = c => fields.map(([k]) => `<div class="${k === 'def' ? 'd' : 't'}">${esc(c[k])}</div>`).join('');

  show(`<button class="btn quiet" data-act="home">← Kutubxona</button>
    <div class="head" style="margin-top:8px"><div style="min-width:0"><h1>${esc(s.title)}</h1>${s.desc ? `<p class="muted" style="margin-top:6px">${esc(s.desc)}</p>` : ''}</div>
      <div class="rowf" id="tools"><button class="btn" data-act="pdf">PDF saqlash</button><button class="btn" data-act="edit">Tahrirlash</button><button class="btn danger" data-act="del">O'chirish</button></div></div>
    <div class="modes${verbs ? ' five' : ''}">
      <button class="mode" data-act="m" data-m="cards"><b>Kartochkalar</b><span class="small muted">Aylantirib ko'rib chiqing</span></button>
      <button class="mode" data-act="m" data-m="learn"><b>O'rganish</b><span class="small muted">Bosqichma-bosqich yodlang</span></button>
      ${verbs ? `<button class="mode" data-act="m" data-m="forms"><b>Shakllar</b><span class="small muted">V2 va V3 ni yozib mashq qiling</span></button>` : ''}
      <button class="mode" data-act="m" data-m="test"><b>Test</b><span class="small muted">Bilimingizni tekshiring</span></button>
      <button class="mode" data-act="m" data-m="match"><b>Juftlash</b><span class="small muted">Vaqtga qarshi o'yin</span></button>
    </div>
    <div class="rowf" style="justify-content:space-between;margin-bottom:10px"><h2 id="tcount"></h2><span class="small muted" id="tdone"></span></div>
    <form class="qadd${verbs ? ' verbs' : ''}" id="qa">${fields.map(f => fieldInput(f, '', true)).join('')}<button class="btn primary">Qo'shish</button></form>
    <div class="rowf qhint"><span class="small muted">${verbs
      ? "Fe'lni yozib Enter bosing — V2 va V3 o'zi to'ldiriladi (noto'g'ri bo'lsa tuzating), tarjimasini yozib yana Enter."
      : "Yozing va Enter bosing — so'z darhol ro'yxatga tushadi."} Tartibni strelkalar yoki sudrash bilan o'zgartirasiz.</span><button class="btn quiet" data-act="bulk">Tayyor ro'yxatdan qo'shish</button></div>
    <input id="tq" type="search" placeholder="${verbs ? "Fe'llar" : 'Atamalar'} ichidan qidirish" aria-label="Qidirish" hidden>
    <div id="tbox"></div>`,
    {
      pdf: async b => {
        b.disabled = true; b.textContent = 'Tayyorlanmoqda…';
        try { await loadPdf(); await window.EnglishPdf.save(s); }
        catch (e) { toast("PDF tayyorlanmadi. Internetni tekshirib, qayta urinib ko'ring."); }
        b.disabled = false; b.textContent = 'PDF saqlash';
      },
      edit: () => nav(editor, id),
      bulk: () => nav(editor, id, true),
      m: b => nav({ cards, learn, forms, test, match }[b.dataset.m], id),
      del: () => { $('#tools').innerHTML = `<span class="small">To'plam butunlay o'chirilsinmi?</span><button class="btn danger" data-act="delYes">Ha, o'chirish</button><button class="btn" data-act="delNo">Yo'q</button>`; },
      delYes: async b => {
        b.disabled = true;
        try { await api('DELETE', SETS + '/' + id); S.sets = S.sets.filter(x => x.id !== id); go('#/'); }
        catch (e) { toast(e.message); nav(setView, id); }
      },
      delNo: () => nav(setView, id),
      editRow: b => startEdit(rowId(b)),
      up: b => moveBy(rowId(b), -1, 'up'),
      down: b => moveBy(rowId(b), 1, 'down'),
      rmRow: b => {
        if (s.cards.length <= 2) return toast(`To'plamda kamida 2 ta ${verbs ? "fe'l" : 'atama'} qolishi kerak.`);
        removing = rowId(b); editing = null; draw();
      },
      rmYes: b => { s.cards = s.cards.filter(c => c.id !== rowId(b)); removing = null; save(s); draw(); },
      cancelRow: () => { editing = removing = null; draw(); },
      saveRow,
    });

  // Reordering: arrows move a row one step; dragging the grip drops it anywhere. Off while a search is filtering the list.
  function place(cid, index) {
    const from = s.cards.findIndex(c => c.id === cid);
    if (from < 0 || index === from) return false;
    const [moved] = s.cards.splice(from, 1);
    s.cards.splice(index, 0, moved); save(s);
    editing = removing = null; fresh = cid; draw(); fresh = null;
    return true;
  }
  function moveBy(cid, step, act) {
    const from = s.cards.findIndex(c => c.id === cid), to = from + step;
    if (to < 0 || to >= s.cards.length) return;
    place(cid, to);
    // keep focus on the same button so it can be pressed repeatedly; at the edge fall back to the opposite arrow
    const btn = name => document.querySelector('#tbox .term[data-id="' + cid + '"] [data-act="' + name + '"]');
    const again = btn(act);
    (again && !again.disabled ? again : btn(act === 'up' ? 'down' : 'up'))?.focus();
  }
  function startEdit(cid) { editing = cid; removing = null; draw(); const el = $('#tbox input'); el.focus(); el.select(); }
  function saveRow() {
    const c = card(editing), next = {};
    $$('#tbox .term.busy input').forEach(i => next[i.dataset.k] = i.value.trim());
    if (!complete(next, s.kind)) return toast("Hamma maydon to'ldirilishi kerak.");
    if (fields.some(([k]) => c[k] !== next[k])) { Object.assign(c, next); save(s); }
    editing = null; draw();
  }
  function row({ c, n }) {
    const num = `<span class="num">${n}</span>`, cls = `term${verbs ? ' verbs' : ''}`;
    if (editing === c.id) return `<div class="${cls} busy" data-id="${c.id}">${num}${fields.map(f => fieldInput(f, c[f[0]])).join('')}
      <div class="tact wide"><button class="btn sm primary" data-act="saveRow">Saqlash</button><button class="btn sm" data-act="cancelRow">Bekor</button></div></div>`;
    if (removing === c.id) return `<div class="${cls} busy" data-id="${c.id}">${num}${cells(c)}
      <div class="tact wide"><span class="small">O'chirilsinmi?</span><button class="btn sm danger" data-act="rmYes">Ha</button><button class="btn sm" data-act="cancelRow">Yo'q</button></div></div>`;
    const move = q ? '' : `<button class="ib" data-act="up" aria-label="Tepaga ko'chirish" title="Tepaga ko'chirish"${n === 1 ? ' disabled' : ''}>${ICON.up}</button><button class="ib" data-act="down" aria-label="Pastga ko'chirish" title="Pastga ko'chirish"${n === s.cards.length ? ' disabled' : ''}>${ICON.down}</button>`;
    const grip = q ? num : `<span class="num"><span class="grip" title="Sudrab ko'chirish">${ICON.grip}</span>${n}</span>`;
    return `<div class="${cls}${fresh === c.id ? ' fresh' : ''}" data-id="${c.id}">${grip}${cells(c)}<span class="lvl l${c.lvl || 0}">${LVL[c.lvl || 0]}</span>
      <div class="tact">${move}<button class="ib" data-act="editRow" aria-label="Tahrirlash" title="Tahrirlash">${ICON.edit}</button><button class="ib" data-act="rmRow" aria-label="O'chirish" title="O'chirish">${ICON.trash}</button></div></div>`;
  }
  function draw() {
    $('#tcount').textContent = `${verbs ? "Fe'llar" : 'Atamalar'} (${s.cards.length})`;
    $('#tdone').textContent = `${mastered(s)} / ${s.cards.length} o'zlashtirildi`;
    $('#tq').hidden = s.cards.length <= 8 && !q;
    const list = s.cards.map((c, i) => ({ c, n: i + 1 })).filter(({ c }) => !q || fields.some(([k]) => norm(c[k]).includes(q)));
    $('#tbox').innerHTML = list.length
      ? `<div class="terms"><div class="term thead${verbs ? ' verbs' : ''}"><span>#</span>${fields.map(([, label]) => `<span>${label}</span>`).join('')}<span>Holat</span><span></span></div>${list.map(row).join('')}</div>`
      : `<p class="empty muted">Qidiruv bo'yicha hech narsa topilmadi.</p>`;
  }

  // Quick add: Enter moves to the next empty field; when every field is filled it adds the card and returns to the first field.
  $('#qa').onsubmit = e => {
    e.preventDefault();
    const inputs = $$('#qa input'), first = inputs[0];
    let c = {}; inputs.forEach(i => c[i.dataset.k] = i.value.trim());
    if (c.term && inputs.slice(1).every(i => !i.value.trim())) { // a whole line typed or pasted into the first field
      const parsed = parseLine(first.value, s.kind);
      if (parsed) c = parsed;
    }
    if (verbs && c.term && !c.v2 && !c.v3) { const f = verbForms(c.term); if (f) [c.v2, c.v3] = f; }
    inputs.forEach(i => i.value = c[i.dataset.k] || '');
    const empty = inputs.find(i => !i.value);
    if (empty) return empty.focus();
    if (s.cards.some(x => sameCard(x, c, s.kind))) { toast(`Bu ${verbs ? "fe'l" : "so'z"} ro'yxatda allaqachon bor.`); return first.select(); }
    const made = { id: uid(), ...c, lvl: 0 };
    s.cards.unshift(made); save(s);
    inputs.forEach(i => i.value = ''); q = $('#tq').value = ''; editing = removing = null;
    fresh = made.id; draw(); fresh = null;
    first.focus();
  };
  $('#tq').oninput = e => { q = norm(e.target.value); editing = removing = null; draw(); };
  $('#tbox').onkeydown = e => {
    if (e.target.tagName !== 'INPUT') return;
    if (e.key === 'Enter') { e.preventDefault(); saveRow(); }
    else if (e.key === 'Escape') { editing = null; draw(); }
  };
  // Drag and drop: a row becomes draggable only while its grip is held, so text in the table can still be selected.
  let dragId = null, drop = null;
  const box = $('#tbox'), clearMarks = () => $$('.drop-before, .drop-after', box).forEach(r => r.classList.remove('drop-before', 'drop-after'));
  box.onpointerdown = e => { const g = e.target.closest('.grip'); if (g) g.closest('.term').draggable = true; };
  box.onpointerup = () => $$('.term[draggable]', box).forEach(r => r.removeAttribute('draggable'));
  box.ondragstart = e => {
    const r = e.target.closest?.('.term[data-id]'); if (!r) return;
    dragId = r.dataset.id; drop = null; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', dragId);
    setTimeout(() => r.classList.add('dragging'));
  };
  box.ondragover = e => {
    const r = dragId && e.target.closest('.term[data-id]'); if (!r) return;
    e.preventDefault(); clearMarks();
    const rect = r.getBoundingClientRect(), before = e.clientY < rect.top + rect.height / 2;
    r.classList.add(before ? 'drop-before' : 'drop-after'); drop = { id: r.dataset.id, before };
  };
  box.ondrop = e => {
    if (!dragId || !drop) return;
    e.preventDefault();
    const cid = dragId, rest = s.cards.filter(c => c.id !== cid);
    const index = rest.findIndex(c => c.id === drop.id) + (drop.before ? 0 : 1);
    dragId = null;
    if (!place(cid, index)) draw();
  };
  box.ondragend = () => { if (dragId) { dragId = null; draw(); } };
  $('#tbox').ondblclick = e => { const r = e.target.closest('.term[data-id]'); if (r && !r.classList.contains('busy')) startEdit(r.dataset.id); };
  draw();
}

/* ---------- Editor ---------- */
function editor(id, openPaste) {
  const s = id ? get(id) : null;
  let kind = s ? (s.kind || 'words') : 'words';   // chosen once, when the set is created
  const row = (c = {}) => `<div class="erow" data-id="${c.id || ''}">${fieldsOf(kind).map(f => fieldInput(f, c[f[0]])).join('')}<button type="button" class="btn quiet" data-act="rm" aria-label="Qatorni o'chirish">✕</button></div>`;
  const read = () => $$('.erow').map(r => { const c = { id: r.dataset.id }; $$('input', r).forEach(i => c[i.dataset.k] = i.value.trim()); return c; });
  const blank = c => fieldsOf(kind).every(([k]) => !c[k]);
  const pasteHelp = () => kind === 'verbs'
    ? ["Har bir qatorga bitta fe'l yozing: <b>go - went - gone - bormoq</b>. Ajratuvchi sifatida tire, tab yoki vergul ishlatish mumkin.", 'go - went - gone - bormoq\nwork - worked - worked - ishlamoq']
    : ["Har bir qatorga bitta juftlik yozing: <b>atama - ta'rif</b>. Ajratuvchi sifatida tire, tab, tenglik yoki vergul ishlatish mumkin.", 'apple - olma\nbook - kitob'];
  const rowsHelp = () => "Enter — keyingi maydonga o'tadi, oxirgi qatorda yangi qator ochadi." + (kind === 'verbs' ? " V1 ni yozsangiz, V2 va V3 o'zi to'ldiriladi — noto'g'ri bo'lsa tuzating." : '');
  // Everything that depends on the set kind is redrawn here, keeping what was already typed.
  function drawRows(list) {
    $('#rows').className = 'stack' + (kind === 'verbs' ? ' verbs' : '');
    $('#rows').innerHTML = list.map(row).join('');
    $('#rows-title').textContent = kind === 'verbs' ? "Fe'llar" : 'Atamalar';
    $('#rows-help').textContent = rowsHelp();
    const [help, sample] = pasteHelp(); $('#paste-help').innerHTML = help; $('#f-paste').placeholder = sample;
  }

  show(`<form id="ef" class="stack">
      <div class="head" style="margin:0"><h1>${s ? "To'plamni tahrirlash" : "Yangi to'plam"}</h1>
        <div class="rowf"><button type="button" class="btn" data-act="cancel">Bekor qilish</button><button class="btn primary">Saqlash</button></div></div>
      <p class="err" id="err" hidden></p>
      <input id="f-title" placeholder="To'plam nomi, masalan: Ingliz tili, 5-dars" aria-label="To'plam nomi" value="${esc(s?.title)}">
      <input id="f-desc" placeholder="Qisqa izoh (ixtiyoriy)" aria-label="Izoh" value="${esc(s?.desc)}">
      ${s ? '' : `<fieldset class="kinds"><legend class="label">To'plam turi</legend>
        <label class="kind"><input type="radio" name="kind" id="k-words" value="words" checked><span><b>So'zlar</b><span class="small muted">Atama va tarjimasi</span></span></label>
        <label class="kind"><input type="radio" name="kind" id="k-verbs" value="verbs"><span><b>Fe'llar</b><span class="small muted">Uch shakli bilan: go – went – gone</span></span></label>
      </fieldset>`}
      <div class="rowf" style="justify-content:space-between;margin-top:6px"><h2 id="rows-title"></h2><button type="button" class="btn quiet" data-act="paste">Tayyor ro'yxatdan qo'shish</button></div>
      <div class="panel stack" id="pastebox"${openPaste ? '' : ' hidden'}>
        <p class="small muted" id="paste-help"></p>
        <textarea id="f-paste" aria-label="Ro'yxat"></textarea>
        <div class="rowf end"><button type="button" class="btn primary" data-act="addPaste">Ro'yxatga qo'shish</button></div>
      </div>
      <p class="small muted" id="rows-help"></p>
      <div id="rows" style="gap:8px"></div>
      <div><button type="button" class="btn" data-act="add">+ Qator qo'shish</button></div>
    </form>`,
    {
      cancel: () => nav(s ? setView : home, id),
      add: () => { $('#rows').insertAdjacentHTML('beforeend', row()); $('#rows').lastElementChild.querySelector('input').focus(); },
      rm: b => b.closest('.erow').remove(),
      paste: () => { const p = $('#pastebox'); p.hidden = !p.hidden; if (!p.hidden) $('#f-paste').focus(); },
      addPaste: () => {
        const found = $('#f-paste').value.split('\n').map(line => parseLine(line, kind)).filter(Boolean);
        if (!found.length) return fail(kind === 'verbs'
          ? "Ro'yxatdan fe'l topilmadi. Har qatorda to'rtta qism bo'lsin: go - went - gone - bormoq."
          : "Ro'yxatdan juftlik topilmadi. Har qatorda atama va ta'rif orasiga tire qo'ying.");
        drawRows([...read().filter(c => !blank(c)), ...found]);
        $('#f-paste').value = ''; $('#pastebox').hidden = true; $('#err').hidden = true;
      },
    });
  drawRows(s ? s.cards : [{}, {}, {}]);
  $$('input[name="kind"]').forEach(r => r.onchange = () => {
    const typed = read().filter(c => !blank(c));
    kind = r.value;
    drawRows(typed.length ? typed : [{}, {}, {}]);
  });

  function fail(msg) { const e = $('#err'); e.textContent = msg; e.hidden = false; e.scrollIntoView({ block: 'center' }); }
  $('#ef').onsubmit = async e => {
    e.preventDefault();
    const title = $('#f-title').value.trim();
    const old = new Map((s ? s.cards : []).map(c => [c.id, c]));
    // A card whose text did not change keeps its learning progress.
    const list = read().filter(c => complete(c, kind))
      .map(c => { const o = old.get(c.id); return o && fieldsOf(kind).every(([k]) => o[k] === c[k]) ? o : { ...c, id: uid(), lvl: 0 }; });
    if (!title) return fail("To'plamga nom bering.");
    if (list.length < 2) return fail(kind === 'verbs' ? "Kamida 2 ta fe'lni uchala shakli va tarjimasi bilan to'ldiring." : "Kamida 2 ta atama va ta'rifini to'ldiring.");
    const desc = $('#f-desc').value.trim();
    const btn = $('#ef button.primary'); btn.disabled = true;
    try {
      if (s) { if (pending.has(s.id)) flush(s); await s.saving; Object.assign(s, await api('PUT', SETS + '/' + id, { title, desc, cards: list, best: s.best })); }
      else { const made = await api('POST', SETS, { title, desc, kind, cards: list }); S.sets.unshift(made); id = made.id; }
      go('#/set/' + id);
    } catch (err) { btn.disabled = false; fail(err.message); }
  };
  // Enter walks through the fields instead of submitting the whole form by accident.
  $('#rows').onkeydown = e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const r = e.target.closest('.erow'), inputs = $$('input', r), i = inputs.indexOf(e.target);
    if (e.target.dataset.k === 'term') suggestForms(r);
    const next = inputs.slice(i + 1).find(x => !x.value.trim()) || inputs[i + 1];
    if (next) return next.focus();
    r.nextElementSibling ? r.nextElementSibling.querySelector('input').focus() : acts.add();
  };
  $('#rows').addEventListener('focusout', e => { if (e.target.dataset?.k === 'term') suggestForms(e.target.closest('.erow')); });
  if (openPaste) $('#f-paste').focus(); else if (!s) $('#f-title').focus();
}

/* ---------- shared study pieces ---------- */
const shead = (id, pct, label) => `<div class="shead"><button class="btn quiet" data-act="open" data-id="${id}">← To'plam</button><div class="bar"><i style="width:${pct}%"></i></div><span class="n">${label}</span></div>`;
function options(set, card) {
  const seen = new Set([norm(card.def)]), out = [];
  for (const c of shuffle(set.cards)) { const k = norm(c.def); if (!seen.has(k)) { seen.add(k); out.push(c.def); } if (out.length === 3) break; }
  return shuffle([card.def, ...out]);
}

/* ---------- Flashcards ---------- */
function cards(id, only) {
  const s = get(id); let order = only || s.cards, i = 0, yes = [], no = [];
  function draw() {
    if (i >= order.length) return end();
    const c = order[i];
    show(`${shead(id, Math.round(i / order.length * 100), `${i + 1} / ${order.length}`)}
      <button class="flip" id="flip" aria-label="Kartochkani aylantirish"><span class="flip-in"><span class="face" data-side="Atama">${esc(c.term)}</span><span class="face back" data-side="${isVerbs(s) ? "V2 · V3 va tarjima" : "Ta'rif"}">${isVerbs(s) ? `<span class="forms">${esc(c.v2)} · ${esc(c.v3)}</span>` : ''}${esc(c.def)}</span></span></button>
      <p class="hint small muted">Aylantirish uchun kartochkani bosing</p>
      <div class="controls"><button class="btn back" data-act="back" ${i ? '' : 'disabled'}>← Orqaga</button><button class="btn no" data-act="no">Hali bilmayman</button><button class="btn yes" data-act="yes">Bilaman</button></div>`,
      { back, no: () => step(no), yes: () => step(yes) },
      e => { if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); flip(); } else if (e.key === 'ArrowRight') step(yes); else if (e.key === 'ArrowLeft') back(); });
    $('#flip').onclick = flip;
  }
  const flip = () => $('#flip')?.classList.toggle('on');
  function step(pile) { pile.push(order[i]); i++; draw(); }
  function back() { if (!i) return; i--; yes = yes.filter(c => c !== order[i]); no = no.filter(c => c !== order[i]); draw(); }
  function end() {
    show(`${shead(id, 100, 'Tugadi')}<div class="panel stack">
        <h1>Barcha kartochkalar ko'rib chiqildi</h1>
        <p><b>${yes.length}</b> tasini bilasiz, <b>${no.length}</b> tasini hali o'rganish kerak.</p>
        <div class="rowf">${no.length ? `<button class="btn primary" data-act="redo">Bilmaganlarimni takrorlash</button>` : ''}<button class="btn" data-act="again">Boshidan boshlash</button><button class="btn" data-act="open" data-id="${id}">To'plamga qaytish</button></div>
      </div>`, { redo: () => nav(cards, id, shuffle(no)), again: () => nav(cards, id, shuffle(s.cards)) });
  }
  draw();
}

/* ---------- Learn: multiple choice first, then typing; a card is mastered after both ---------- */
function learn(id) {
  const s = get(id); let queue = [], cur, type, ok = 0, bad = 0;
  const left = () => s.cards.filter(c => (c.lvl || 0) < 2);
  const head = () => { const p = Math.round(s.cards.reduce((a, c) => a + (c.lvl || 0), 0) / (s.cards.length * 2) * 100); return shead(id, p, p + '%'); };
  function round() { ok = bad = 0; queue = shuffle(left()).slice(0, 7); queue.length ? ask() : done(); }
  function ask() {
    cur = queue[0];
    const opts = options(s, cur);
    type = (cur.lvl || 0) >= 1 || opts.length < 2 ? 'write' : 'mc';
    if (type === 'mc') {
      const pick = n => {
        keyFn = null; acts.pick = null;
        $$('.opt').forEach((b, j) => { b.disabled = true; if (opts[j] === cur.def) b.classList.add('right'); else if (j === n) b.classList.add('wrong'); });
        if (opts[n] === cur.def) { apply(true); timers.push(setTimeout(next, 650)); }
        else { $('#box').insertAdjacentHTML('beforeend', `<div class="rowf end"><button class="btn primary" data-act="cont">Davom etish</button></div>`); acts.cont = () => { apply(false); next(); }; keyFn = e => { if (e.key === 'Enter') acts.cont(); }; }
      };
      show(`${head()}<div class="panel stack" id="box"><div><div class="label">To'g'ri ta'rifni tanlang</div><div class="prompt">${esc(cur.term)}</div>
        <div class="opts">${opts.map((o, j) => `<button class="btn opt" data-act="pick" data-i="${j}"><b>${j + 1}</b>${esc(o)}</button>`).join('')}</div></div></div>`,
        { pick: b => pick(+b.dataset.i) }, e => { const n = +e.key - 1; if (n >= 0 && n < opts.length) pick(n); });
    } else {
      show(`${head()}<form class="panel stack" id="wf"><div><div class="label">Ta'rifini yozing</div><div class="prompt" style="margin-bottom:0">${esc(cur.term)}</div></div>
        <input id="ans" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Javobingiz" aria-label="Javob">
        <div class="rowf end"><button type="button" class="btn quiet" data-act="skip">Bilmayman</button><button class="btn primary">Tekshirish</button></div></form>`,
        { skip: () => wrong('') });
      $('#wf').onsubmit = e => { e.preventDefault(); const v = $('#ans').value; if (!v.trim()) return; isRight(v, cur.def) ? right() : wrong(v); };
      $('#ans').focus();
    }
  }
  function right() {
    show(`${head()}<div class="panel stack"><div class="prompt" style="margin:0">${esc(cur.term)}</div><div class="note ok">To'g'ri: ${esc(cur.def)}</div></div>`);
    apply(true); timers.push(setTimeout(next, 800));
  }
  function wrong(v) {
    show(`${head()}<div class="panel stack"><div class="prompt" style="margin:0">${esc(cur.term)}</div>
      <div class="note bad">${v ? `<div class="small">Siz yozdingiz: ${esc(v)}</div>` : ''}<div><b>To'g'ri javob:</b> ${esc(cur.def)}</div></div>
      <div class="rowf end">${v ? `<button class="btn quiet" data-act="over">Men to'g'ri yozgan edim</button>` : ''}<button class="btn primary" data-act="cont">Davom etish</button></div></div>`,
      { cont: () => { apply(false); next(); }, over: () => { apply(true); next(); } }, e => { if (e.key === 'Enter') { apply(false); next(); } });
  }
  function apply(good) {
    queue.shift();
    if (good) { ok++; cur.lvl = type === 'write' ? 2 : 1; }
    else { bad++; cur.lvl = 0; queue.splice(Math.min(3, queue.length), 0, cur); }
    save(s);
  }
  function next() { queue.length ? ask() : left().length ? roundEnd() : done(); }
  function roundEnd() {
    show(`${head()}<div class="panel stack"><h1>Davra tugadi</h1>
      <p><b>${ok}</b> ta to'g'ri, <b>${bad}</b> ta xato. O'zlashtirildi: <b>${mastered(s)} / ${s.cards.length}</b>.</p>
      <div class="rowf"><button class="btn primary" data-act="go">Keyingi davra</button><button class="btn" data-act="open" data-id="${id}">To'plamga qaytish</button></div></div>`,
      { go: round }, e => { if (e.key === 'Enter') round(); });
  }
  function done() {
    show(`${shead(id, 100, '100%')}<div class="panel stack"><h1>Hamma atamalar o'zlashtirildi</h1>
      <p class="muted">Bilimingizni mustahkamlash uchun test topshirib ko'ring yoki o'rganishni qaytadan boshlang.</p>
      <div class="rowf"><button class="btn primary" data-act="test">Test topshirish</button><button class="btn" data-act="reset">Qaytadan o'rganish</button><button class="btn" data-act="open" data-id="${id}">To'plamga qaytish</button></div></div>`,
      { test: () => nav(test, id), reset: () => { s.cards.forEach(c => c.lvl = 0); save(s); round(); } });
  }
  round();
}

/* ---------- Forms: given V1 and the translation, type V2 and V3; missed verbs come back until all are right ---------- */
function forms(id) {
  const s = get(id);
  const total = s.cards.length;
  let queue = shuffle(s.cards), cur, firstTry = 0, missed = new Set();
  const head = () => { const done = total - queue.length; return shead(id, Math.round(done / total * 100), `${done} / ${total}`); };
  const verb = () => `<div><div class="label">V2 va V3 shakllarini yozing</div><div class="prompt" style="margin-bottom:4px">${esc(cur.term)}</div><p class="muted">${esc(cur.def)}</p></div>`;
  function ask() {
    cur = queue[0];
    show(`${head()}<form class="panel stack" id="ff">${verb()}
      <div class="pairf">
        <label class="field">V2 — o'tgan zamon<input id="f-v2" autocomplete="off" autocapitalize="off" spellcheck="false"></label>
        <label class="field">V3 — o'tgan zamon sifatdoshi<input id="f-v3" autocomplete="off" autocapitalize="off" spellcheck="false"></label>
      </div>
      <div class="rowf end"><button type="button" class="btn quiet" data-act="skip">Bilmayman</button><button class="btn primary">Tekshirish</button></div></form>`,
      { skip: () => check('', '') });
    $('#ff').onsubmit = e => {
      e.preventDefault();
      const a = $('#f-v2').value.trim(), b = $('#f-v3').value.trim();
      if (!a) return $('#f-v2').focus();
      if (!b) return $('#f-v3').focus();   // Enter in V2 moves on to V3
      check(a, b);
    };
    $('#f-v2').focus();
  }
  function check(a, b) {
    const okA = isRight(a, cur.v2), okB = isRight(b, cur.v3);
    const line = (name, given, right, ok) => ok
      ? `<div class="note ok">${name}: ${esc(right)}</div>`
      : `<div class="note bad"><div class="small">${name} — ${given ? 'siz yozdingiz: ' + esc(given) : 'javob berilmagan'}</div><div><b>To'g'ri javob:</b> ${esc(right)}</div></div>`;
    queue.shift();
    if (okA && okB) {
      if (!missed.has(cur.id)) firstTry++;
      show(`${head()}<div class="panel stack">${verb()}${line('V2', a, cur.v2, true)}${line('V3', b, cur.v3, true)}</div>`);
      timers.push(setTimeout(next, 900));
    } else {
      missed.add(cur.id);
      queue.splice(Math.min(3, queue.length), 0, cur);   // comes back a few verbs later
      show(`${head()}<div class="panel stack">${verb()}${line('V2', a, cur.v2, okA)}${line('V3', b, cur.v3, okB)}
        <div class="rowf end"><button class="btn primary" data-act="cont">Davom etish</button></div></div>`,
        { cont: next }, e => { if (e.key === 'Enter') next(); });
    }
  }
  function next() { queue.length ? ask() : done(); }
  function done() {
    const hard = s.cards.filter(c => missed.has(c.id));
    show(`${shead(id, 100, `${total} / ${total}`)}<div class="panel stack"><h1>Mashq tugadi</h1>
      <p>${total} ta fe'ldan <b>${firstTry}</b> tasini birinchi urinishda to'g'ri yozdingiz.</p>
      ${hard.length ? `<div><div class="label" style="margin-bottom:6px">Qiynalgan fe'llaringiz</div>${hard.map(c => `<div><b>${esc(c.term)}</b> – ${esc(c.v2)} – ${esc(c.v3)}</div>`).join('')}</div>` : ''}
      <div class="rowf"><button class="btn primary" data-act="again">Yana mashq qilish</button><button class="btn" data-act="open" data-id="${id}">To'plamga qaytish</button></div></div>`,
      { again: () => nav(forms, id) });
  }
  ask();
}

/* ---------- Test: a mix of multiple choice, true/false and typed answers ---------- */
function test(id) {
  const s = get(id);
  const qs = shuffle(s.cards).slice(0, 20).map((card, n) => {
    const opts = options(s, card);
    const kinds = isVerbs(s) ? ['mc', 'forms', 'tf', 'write'] : ['mc', 'tf', 'write'];   // verb sets also ask for V2 and V3
    let type = kinds[n % kinds.length];
    if (opts.length < 2 && (type === 'mc' || type === 'tf')) type = 'write';
    if (type === 'tf') { const truth = Math.random() < .5; return { type, card, truth, shown: truth ? card.def : opts.find(o => o !== card.def) }; }
    return { type, card, opts };
  });
  const body = (q, n) => q.type === 'mc' ? q.opts.map((o, j) => `<label class="choice"><input type="radio" name="q${n}" value="${j}">${esc(o)}</label>`).join('')
    : q.type === 'tf' ? `<p>Ta'rifi: <b>${esc(q.shown)}</b></p><label class="choice"><input type="radio" name="q${n}" value="1">To'g'ri</label><label class="choice"><input type="radio" name="q${n}" value="0">Noto'g'ri</label>`
    : q.type === 'forms' ? `<p class="small muted">${esc(q.card.def)} — V2 va V3 shakllarini yozing</p><div class="pairf"><input name="q${n}a" id="t-${n}a" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="V2" aria-label="V2"><input name="q${n}b" id="t-${n}b" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="V3" aria-label="V3"></div>`
    : `<input name="q${n}" id="t-${n}" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Ta'rifini yozing" aria-label="Javob">`;
  show(`${shead(id, 0, `${qs.length} savol`)}<form id="tf" class="stack"><h1>Test</h1>
    ${qs.map((q, n) => `<div class="q" id="q${n}"><div class="label">${n + 1}-savol</div><div class="p">${esc(q.card.term)}</div>${body(q, n)}</div>`).join('')}
    <div class="rowf end"><button class="btn primary">Testni tekshirish</button></div></form>`);
  $('#tf').onsubmit = e => {
    e.preventDefault();
    const f = new FormData(e.target); let score = 0;
    const out = qs.map((q, n) => {
      const v = f.get('q' + n);
      let good, given, right = q.card.def;
      if (q.type === 'mc') { given = v == null ? '' : q.opts[+v]; good = given === q.card.def; }
      else if (q.type === 'tf') { given = v == null ? '' : v === '1' ? "To'g'ri" : "Noto'g'ri"; good = v != null && (v === '1') === q.truth; }
      else if (q.type === 'forms') {
        const a = (f.get(`q${n}a`) || '').trim(), b = (f.get(`q${n}b`) || '').trim();
        given = [a, b].filter(Boolean).join(', '); right = `${q.card.v2}, ${q.card.v3}`;
        good = isRight(a, q.card.v2) && isRight(b, q.card.v3);
      }
      else { given = (v || '').trim(); good = isRight(given, q.card.def); }
      if (good) score++;
      return `<div class="q ${good ? 'ok' : 'bad'}"><div class="label">${n + 1}-savol · ${good ? "to'g'ri" : 'xato'}</div><div class="p">${esc(q.card.term)}</div>
        ${q.type === 'tf' ? `<p class="small muted">Ko'rsatilgan ta'rif: ${esc(q.shown)}</p>` : ''}
        ${good ? `<div class="note ok">${esc(right)}</div>` : `<div class="note bad"><div class="small">Sizning javobingiz: ${given ? esc(given) : 'javob berilmagan'}</div><div><b>To'g'ri javob:</b> ${esc(right)}</div></div>`}</div>`;
    });
    const pct = Math.round(score / qs.length * 100);
    show(`${shead(id, pct, pct + '%')}<div class="stack"><div class="panel stack"><div class="big">${pct}%</div>
      <p>${qs.length} ta savoldan <b>${score}</b> tasiga to'g'ri javob berdingiz.</p>
      <div class="rowf"><button class="btn primary" data-act="again">Yangi test</button><button class="btn" data-act="open" data-id="${id}">To'plamga qaytish</button></div></div>${out.join('')}</div>`,
      { again: () => nav(test, id) });
    window.scrollTo(0, 0);
  };
}

/* ---------- Match: pair terms with definitions against the clock ---------- */
function match(id) {
  const s = get(id);
  const picked = shuffle(s.cards).slice(0, 6);
  const tiles = shuffle(picked.flatMap(c => [{ c, text: c.term }, { c, text: c.def }]));
  let sel = null, left = picked.length, penalty = 0, busy = false;
  const t0 = Date.now(), secs = () => (Date.now() - t0) / 1000 + penalty, fmt = x => x.toFixed(1) + ' s';
  show(`${shead(id, 0, '')}<div class="rowf" style="justify-content:space-between;margin-bottom:14px"><p class="muted">Atamani o'z ta'rifi bilan juftlang. Xato uchun +1 soniya.</p><span class="timer" id="tm">0.0 s</span></div>
    <div class="board">${tiles.map((t, n) => `<button class="tile" data-act="t" data-n="${n}">${esc(t.text)}</button>`).join('')}</div>`,
    { t: b => {
      if (busy || b.classList.contains('good')) return;
      if (sel === b) { b.classList.remove('sel'); sel = null; return; }
      if (!sel) { sel = b; b.classList.add('sel'); return; }
      const a = sel; sel = null; a.classList.remove('sel');
      if (tiles[a.dataset.n].c === tiles[b.dataset.n].c) { a.classList.add('good'); b.classList.add('good'); if (!--left) finish(); }
      else { penalty++; busy = true; a.classList.add('miss'); b.classList.add('miss'); timers.push(setTimeout(() => { a.classList.remove('miss'); b.classList.remove('miss'); busy = false; }, 450)); }
    } });
  $('.shead .n').remove();
  const tick = setInterval(() => { const el = $('#tm'); if (el) el.textContent = fmt(secs()); }, 100);
  timers.push(tick);
  function finish() {
    clearInterval(tick);
    const time = secs(), record = !s.best || time < s.best;
    if (record) { s.best = time; save(s); }
    timers.push(setTimeout(() => show(`${shead(id, 100, '')}<div class="panel stack"><div class="big">${fmt(time)}</div>
      <p>${record ? 'Bu sizning yangi rekordingiz.' : `Eng yaxshi natijangiz: <b>${fmt(s.best)}</b>.`}</p>
      <div class="rowf"><button class="btn primary" data-act="again">Yana o'ynash</button><button class="btn" data-act="open" data-id="${id}">To'plamga qaytish</button></div></div>`,
      { again: () => nav(match, id) }), 300));
  }
}

/* ---------- routing: "#/" is the library, "#/set/ID" is one set; study modes live inside a set ---------- */
function go(hash) { if (location.hash === hash) route(); else location.hash = hash; }
function route() {
  const m = /^#\/set\/([\w-]+)/.exec(location.hash);
  if (m) return nav(setView, m[1]);
  nav(home);
}
addEventListener('hashchange', route);

(async () => {
  show(`<p class="muted">Yuklanmoqda…</p>`);
  try { S.sets = await api('GET', SETS); route(); }
  catch (e) { show(`<div class="panel stack"><h1>Bo'lim ochilmadi</h1><p class="muted">${esc(e.message)}</p><div><button class="btn primary" data-act="retry">Qayta urinish</button></div></div>`, { retry: () => location.reload() }); }
})();
})();
