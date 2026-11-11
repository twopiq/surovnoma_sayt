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

/* ---------- Library ---------- */
function home() {
  const cards = list => list.length ? `<div class="grid">${list.map(s => `
    <button class="set" data-act="open" data-id="${s.id}">
      <b>${esc(s.title)}</b><span class="grow"></span>
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

/* ---------- Set page ---------- */
const LVL = ['Yangi', "O'rganilmoqda", "O'zlashtirildi"];
const ICON = {
  edit: '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.5 3.5l3 3L7 16H4v-3z"/><path d="M11.5 5.5l3 3"/></svg>',
  trash: '<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h12M8 6V4h4v2M6 6l.7 10h6.6L14 6"/></svg>',
};
function setView(id) {
  const s = get(id); if (!s) return home();
  // q: search text; editing / removing: id of the row in that state; fresh: id of the row just added
  let q = '', editing = null, removing = null, fresh = null;
  const rowId = b => b.closest('.term').dataset.id;
  const card = cid => s.cards.find(c => c.id === cid);

  show(`<button class="btn quiet" data-act="home">← Kutubxona</button>
    <div class="head" style="margin-top:8px"><div style="min-width:0"><h1>${esc(s.title)}</h1>${s.desc ? `<p class="muted" style="margin-top:6px">${esc(s.desc)}</p>` : ''}</div>
      <div class="rowf" id="tools"><button class="btn" data-act="edit">Tahrirlash</button><button class="btn danger" data-act="del">O'chirish</button></div></div>
    <div class="modes">
      <button class="mode" data-act="m" data-m="cards"><b>Kartochkalar</b><span class="small muted">Aylantirib ko'rib chiqing</span></button>
      <button class="mode" data-act="m" data-m="learn"><b>O'rganish</b><span class="small muted">Bosqichma-bosqich yodlang</span></button>
      <button class="mode" data-act="m" data-m="test"><b>Test</b><span class="small muted">Bilimingizni tekshiring</span></button>
      <button class="mode" data-act="m" data-m="match"><b>Juftlash</b><span class="small muted">Vaqtga qarshi o'yin</span></button>
    </div>
    <div class="rowf" style="justify-content:space-between;margin-bottom:10px"><h2 id="tcount"></h2><span class="small muted" id="tdone"></span></div>
    <form class="qadd" id="qa">
      <input id="qa-t" placeholder="Yangi so'z" aria-label="Yangi so'z" autocomplete="off" autocapitalize="off" spellcheck="false">
      <input id="qa-d" placeholder="Tarjimasi yoki ta'rifi" aria-label="Tarjimasi yoki ta'rifi" autocomplete="off">
      <button class="btn primary">Qo'shish</button>
    </form>
    <div class="rowf qhint"><span class="small muted">Yozing va Enter bosing — so'z darhol ro'yxatga tushadi.</span><button class="btn quiet" data-act="bulk">Tayyor ro'yxatdan qo'shish</button></div>
    <input id="tq" type="search" placeholder="Atamalar ichidan qidirish" aria-label="Atamalar ichidan qidirish" hidden>
    <div id="tbox"></div>`,
    {
      edit: () => nav(editor, id),
      bulk: () => nav(editor, id, true),
      m: b => nav({ cards, learn, test, match }[b.dataset.m], id),
      del: () => { $('#tools').innerHTML = `<span class="small">To'plam butunlay o'chirilsinmi?</span><button class="btn danger" data-act="delYes">Ha, o'chirish</button><button class="btn" data-act="delNo">Yo'q</button>`; },
      delYes: async b => {
        b.disabled = true;
        try { await api('DELETE', SETS + '/' + id); S.sets = S.sets.filter(x => x.id !== id); go('#/'); }
        catch (e) { toast(e.message); nav(setView, id); }
      },
      delNo: () => nav(setView, id),
      editRow: b => { editing = rowId(b); removing = null; draw(); const el = $('#tbox .et'); el.focus(); el.select(); },
      rmRow: b => {
        if (s.cards.length <= 2) return toast("To'plamda kamida 2 ta atama qolishi kerak.");
        removing = rowId(b); editing = null; draw();
      },
      rmYes: b => { s.cards = s.cards.filter(c => c.id !== rowId(b)); removing = null; save(s); draw(); },
      cancelRow: () => { editing = removing = null; draw(); },
      saveRow,
    });

  function saveRow() {
    const c = card(editing), term = $('#tbox .et').value.trim(), def = $('#tbox .ed').value.trim();
    if (!term || !def) return toast("Atama ham, ta'rifi ham to'ldirilishi kerak.");
    if (c.term !== term || c.def !== def) { c.term = term; c.def = def; save(s); }
    editing = null; draw();
  }
  function row({ c, n }) {
    const num = `<span class="num">${n}</span>`;
    if (editing === c.id) return `<div class="term busy" data-id="${c.id}">${num}<input class="et" aria-label="Atama" value="${esc(c.term)}"><input class="ed" aria-label="Ta'rif" value="${esc(c.def)}">
      <div class="tact wide"><button class="btn sm primary" data-act="saveRow">Saqlash</button><button class="btn sm" data-act="cancelRow">Bekor</button></div></div>`;
    if (removing === c.id) return `<div class="term busy" data-id="${c.id}">${num}<div class="t">${esc(c.term)}</div><div class="d">${esc(c.def)}</div>
      <div class="tact wide"><span class="small">O'chirilsinmi?</span><button class="btn sm danger" data-act="rmYes">Ha</button><button class="btn sm" data-act="cancelRow">Yo'q</button></div></div>`;
    return `<div class="term${fresh === c.id ? ' fresh' : ''}" data-id="${c.id}">${num}<div class="t">${esc(c.term)}</div><div class="d">${esc(c.def)}</div><span class="lvl l${c.lvl || 0}">${LVL[c.lvl || 0]}</span>
      <div class="tact"><button class="ib" data-act="editRow" aria-label="Tahrirlash" title="Tahrirlash">${ICON.edit}</button><button class="ib" data-act="rmRow" aria-label="O'chirish" title="O'chirish">${ICON.trash}</button></div></div>`;
  }
  function draw() {
    $('#tcount').textContent = `Atamalar (${s.cards.length})`;
    $('#tdone').textContent = `${mastered(s)} / ${s.cards.length} o'zlashtirildi`;
    $('#tq').hidden = s.cards.length <= 8 && !q;
    const list = s.cards.map((c, i) => ({ c, n: i + 1 })).filter(({ c }) => !q || norm(c.term).includes(q) || norm(c.def).includes(q));
    $('#tbox').innerHTML = list.length
      ? `<div class="terms"><div class="term thead"><span>#</span><span>Atama</span><span>Ta'rif</span><span>Holat</span><span></span></div>${list.map(row).join('')}</div>`
      : `<p class="empty muted">Qidiruv bo'yicha hech narsa topilmadi.</p>`;
  }

  // Quick add: Enter in the first field moves on, Enter in the second adds the word and returns to the first.
  $('#qa').onsubmit = e => {
    e.preventDefault();
    const t = $('#qa-t'), d = $('#qa-d');
    let term = t.value.trim(), def = d.value.trim();
    if (term && !def) { // "apple - olma" typed or pasted into the first field
      const sep = ['\t', ' - ', ' – ', ' — ', ' = '].find(x => t.value.includes(x));
      if (sep) { const i = t.value.indexOf(sep); term = t.value.slice(0, i).trim(); def = t.value.slice(i + sep.length).trim(); }
    }
    if (!term) return t.focus();
    if (!def) return d.focus();
    if (s.cards.some(c => norm(c.term) === norm(term) && norm(c.def) === norm(def))) { toast("Bu so'z ro'yxatda allaqachon bor."); return t.select(); }
    const c = { id: uid(), term, def, lvl: 0 };
    s.cards.unshift(c); save(s);
    t.value = d.value = ''; q = $('#tq').value = ''; editing = removing = null;
    fresh = c.id; draw(); fresh = null;
    t.focus();
  };
  $('#tq').oninput = e => { q = norm(e.target.value); editing = removing = null; draw(); };
  $('#tbox').onkeydown = e => {
    if (!e.target.matches('.et, .ed')) return;
    if (e.key === 'Enter') { e.preventDefault(); saveRow(); }
    else if (e.key === 'Escape') { editing = null; draw(); }
  };
  $('#tbox').ondblclick = e => { const r = e.target.closest('.term[data-id]'); if (r && !r.classList.contains('busy')) { editing = r.dataset.id; removing = null; draw(); $('#tbox .et').focus(); } };
  draw();
}

/* ---------- Editor ---------- */
function editor(id, openPaste) {
  const s = id ? get(id) : null;
  const row = (c = {}) => `<div class="erow" data-id="${c.id || ''}"><input class="t" placeholder="Atama" aria-label="Atama" value="${esc(c.term)}"><input class="d" placeholder="Ta'rif" aria-label="Ta'rif" value="${esc(c.def)}"><button type="button" class="btn quiet" data-act="rm" aria-label="Qatorni o'chirish">✕</button></div>`;
  show(`<form id="ef" class="stack">
      <div class="head" style="margin:0"><h1>${s ? "To'plamni tahrirlash" : "Yangi to'plam"}</h1>
        <div class="rowf"><button type="button" class="btn" data-act="cancel">Bekor qilish</button><button class="btn primary">Saqlash</button></div></div>
      <p class="err" id="err" hidden></p>
      <input id="f-title" placeholder="To'plam nomi, masalan: Ingliz tili, 5-dars" aria-label="To'plam nomi" value="${esc(s?.title)}">
      <input id="f-desc" placeholder="Qisqa izoh (ixtiyoriy)" aria-label="Izoh" value="${esc(s?.desc)}">
      <div class="rowf" style="justify-content:space-between;margin-top:6px"><h2>Atamalar</h2><button type="button" class="btn quiet" data-act="paste">Tayyor ro'yxatdan qo'shish</button></div>
      <div class="panel stack" id="pastebox"${openPaste ? '' : ' hidden'}>
        <p class="small muted">Har bir qatorga bitta juftlik yozing: <b>atama - ta'rif</b>. Ajratuvchi sifatida tire, tab, tenglik yoki vergul ishlatish mumkin.</p>
        <textarea id="f-paste" aria-label="Ro'yxat" placeholder="apple - olma&#10;book - kitob"></textarea>
        <div class="rowf end"><button type="button" class="btn primary" data-act="addPaste">Ro'yxatga qo'shish</button></div>
      </div>
      <p class="small muted">Enter — keyingi maydonga o'tadi, oxirgi qatorda yangi qator ochadi.</p>
      <div class="stack" id="rows" style="gap:8px">${(s ? s.cards : [{}, {}, {}]).map(row).join('')}</div>
      <div><button type="button" class="btn" data-act="add">+ Qator qo'shish</button></div>
    </form>`,
    {
      cancel: () => nav(s ? setView : home, id),
      add: () => { $('#rows').insertAdjacentHTML('beforeend', row()); $('#rows').lastElementChild.querySelector('.t').focus(); },
      rm: b => b.closest('.erow').remove(),
      paste: () => { const p = $('#pastebox'); p.hidden = !p.hidden; if (!p.hidden) $('#f-paste').focus(); },
      addPaste: () => {
        const found = [];
        for (const line of $('#f-paste').value.split('\n')) {
          const sep = ['\t', ' - ', ' – ', ' — ', '=', ';', ':', ',', '-'].find(x => line.includes(x));
          if (!sep) continue;
          const i = line.indexOf(sep), term = line.slice(0, i).trim(), def = line.slice(i + sep.length).trim();
          if (term && def) found.push({ term, def });
        }
        if (!found.length) return fail("Ro'yxatdan juftlik topilmadi. Har qatorda atama va ta'rif orasiga tire qo'ying.");
        $$('.erow').forEach(r => { if (!r.querySelector('.t').value.trim() && !r.querySelector('.d').value.trim()) r.remove(); });
        $('#rows').insertAdjacentHTML('beforeend', found.map(row).join(''));
        $('#f-paste').value = ''; $('#pastebox').hidden = true; $('#err').hidden = true;
      },
    });
  function fail(msg) { const e = $('#err'); e.textContent = msg; e.hidden = false; e.scrollIntoView({ block: 'center' }); }
  $('#ef').onsubmit = async e => {
    e.preventDefault();
    const title = $('#f-title').value.trim();
    const old = new Map((s ? s.cards : []).map(c => [c.id, c]));
    const list = $$('.erow').map(r => ({ id: r.dataset.id, term: r.querySelector('.t').value.trim(), def: r.querySelector('.d').value.trim() })).filter(c => c.term && c.def)
      .map(c => { const o = old.get(c.id); return o && o.term === c.term && o.def === c.def ? o : { id: uid(), term: c.term, def: c.def, lvl: 0 }; });
    if (!title) return fail("To'plamga nom bering.");
    if (list.length < 2) return fail("Kamida 2 ta atama va ta'rifini to'ldiring.");
    const desc = $('#f-desc').value.trim();
    const btn = $('#ef button.primary'); btn.disabled = true;
    try {
      if (s) { if (pending.has(s.id)) flush(s); await s.saving; Object.assign(s, await api('PUT', SETS + '/' + id, { title, desc, cards: list, best: s.best })); }
      else { const made = await api('POST', SETS, { title, desc, cards: list }); S.sets.unshift(made); id = made.id; }
      go('#/set/' + id);
    } catch (err) { btn.disabled = false; fail(err.message); }
  };
  // Enter walks through the fields instead of submitting the whole form by accident.
  $('#rows').onkeydown = e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const r = e.target.closest('.erow');
    if (e.target.classList.contains('t')) return r.querySelector('.d').focus();
    r.nextElementSibling ? r.nextElementSibling.querySelector('.t').focus() : acts.add();
  };
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
      <button class="flip" id="flip" aria-label="Kartochkani aylantirish"><span class="flip-in"><span class="face" data-side="Atama">${esc(c.term)}</span><span class="face back" data-side="Ta'rif">${esc(c.def)}</span></span></button>
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

/* ---------- Test: a mix of multiple choice, true/false and typed answers ---------- */
function test(id) {
  const s = get(id);
  const qs = shuffle(s.cards).slice(0, 20).map((card, n) => {
    const opts = options(s, card);
    let type = ['mc', 'tf', 'write'][n % 3];
    if (opts.length < 2 && type !== 'write') type = 'write';
    if (type === 'tf') { const truth = Math.random() < .5; return { type, card, truth, shown: truth ? card.def : opts.find(o => o !== card.def) }; }
    return { type, card, opts };
  });
  const body = (q, n) => q.type === 'mc' ? q.opts.map((o, j) => `<label class="choice"><input type="radio" name="q${n}" value="${j}">${esc(o)}</label>`).join('')
    : q.type === 'tf' ? `<p>Ta'rifi: <b>${esc(q.shown)}</b></p><label class="choice"><input type="radio" name="q${n}" value="1">To'g'ri</label><label class="choice"><input type="radio" name="q${n}" value="0">Noto'g'ri</label>`
    : `<input name="q${n}" id="t-${n}" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Ta'rifini yozing" aria-label="Javob">`;
  show(`${shead(id, 0, `${qs.length} savol`)}<form id="tf" class="stack"><h1>Test</h1>
    ${qs.map((q, n) => `<div class="q" id="q${n}"><div class="label">${n + 1}-savol</div><div class="p">${esc(q.card.term)}</div>${body(q, n)}</div>`).join('')}
    <div class="rowf end"><button class="btn primary">Testni tekshirish</button></div></form>`);
  $('#tf').onsubmit = e => {
    e.preventDefault();
    const f = new FormData(e.target); let score = 0;
    const out = qs.map((q, n) => {
      const v = f.get('q' + n);
      let good, given;
      if (q.type === 'mc') { given = v == null ? '' : q.opts[+v]; good = given === q.card.def; }
      else if (q.type === 'tf') { given = v == null ? '' : v === '1' ? "To'g'ri" : "Noto'g'ri"; good = v != null && (v === '1') === q.truth; }
      else { given = (v || '').trim(); good = isRight(given, q.card.def); }
      if (good) score++;
      return `<div class="q ${good ? 'ok' : 'bad'}"><div class="label">${n + 1}-savol · ${good ? "to'g'ri" : 'xato'}</div><div class="p">${esc(q.card.term)}</div>
        ${q.type === 'tf' ? `<p class="small muted">Ko'rsatilgan ta'rif: ${esc(q.shown)}</p>` : ''}
        ${good ? `<div class="note ok">${esc(q.card.def)}</div>` : `<div class="note bad"><div class="small">Sizning javobingiz: ${given ? esc(given) : 'javob berilmagan'}</div><div><b>To'g'ri javob:</b> ${esc(q.card.def)}</div></div>`}</div>`;
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
