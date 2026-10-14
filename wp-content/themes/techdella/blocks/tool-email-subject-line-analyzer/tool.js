// Email Subject Line Analyzer (§12.1). A transparent rubric, shown line by line:
// every point the score gives or takes away is listed with the reason.
import { tool, el, section, checks, num } from '../tool-kit/kit.js';

const SPAM = ['free', 'guarantee', 'guaranteed', 'act now', 'urgent', 'winner', 'you won', 'cash', 'risk-free', 'risk free', 'click here', 'buy now', 'order now', 'limited time', 'no cost', 'earn money', 'make money', 'double your', '100%', '$$$', 'miracle', 'cheap', 'lowest price', 'once in a lifetime', 'congratulations', 'dear friend', 'no obligation', 'special promotion'];
const EMOJI = /\p{Extended_Pictographic}/gu;

export function analyze(subject, preheader = '') {
  const s = subject.trim();
  const chars = [...s].length;
  const wordList = s.split(/\s+/).filter(Boolean);
  const lower = s.toLowerCase();
  const rows = [];
  let score = 0;
  const add = (pts, max, status, label, detail) => { score += pts; rows.push({ status, label: `${label} (${pts}/${max})`, detail }); };

  // 1. Length (25)
  if (chars >= 25 && chars <= 50) add(25, 25, 'pass', `Length: ${chars} characters`, 'Short enough to show in full on most phones.');
  else if (chars >= 15 && chars <= 65) add(15, 25, 'warn', `Length: ${chars} characters`, chars < 25 ? 'Very short. Make sure it still says what the email is about.' : 'Phones often cut subjects around 35–50 characters. Put the key words first.');
  else add(5, 25, 'fail', `Length: ${chars} characters`, chars < 15 ? 'Too short to tell readers what’s inside.' : 'Long subjects get cut off. Trim to the point.');

  // 2. Spam-trigger words (20)
  const hits = SPAM.filter((w) => new RegExp(`(^|[^a-z])${w.replace(/[$%]/g, '\\$&')}([^a-z]|$)`, 'i').test(lower));
  if (!hits.length) add(20, 20, 'pass', 'No common spam-trigger phrases', 'Filters weigh many signals, but these phrases are best avoided.');
  else add(Math.max(0, 20 - hits.length * 8), 20, 'fail', `Spam-trigger phrases: ${hits.join(', ')}`, 'Rephrase these. They make filters and readers suspicious.');

  // 3. Capitals (15)
  const caps = wordList.filter((w) => w.replace(/[^A-Za-z]/g, '').length >= 3 && w === w.toUpperCase() && /[A-Z]/.test(w));
  if (!caps.length) add(15, 15, 'pass', 'No ALL-CAPS words', 'Normal case reads as calm and credible.');
  else add(caps.length === 1 ? 8 : 0, 15, caps.length === 1 ? 'warn' : 'fail', `ALL-CAPS words: ${caps.join(', ')}`, 'Capitals read as shouting. Keep acronyms, lowercase the rest.');

  // 4. Punctuation (10)
  const bangs = (s.match(/!/g) || []).length;
  const symbols = (s.match(/[$%*#@]/g) || []).length;
  if (!bangs && symbols < 2) add(10, 10, 'pass', 'Calm punctuation', 'No exclamation marks or symbol runs.');
  else add(bangs === 1 && symbols < 2 ? 6 : 0, 10, bangs > 1 || symbols > 1 ? 'fail' : 'warn', `${bangs} exclamation mark${bangs === 1 ? '' : 's'}, ${symbols} symbol${symbols === 1 ? '' : 's'}`, 'One exclamation mark at most. Symbols like $$$ look like spam.');

  // 5. Words (10)
  const n = wordList.length;
  add(n >= 4 && n <= 9 ? 10 : 5, 10, n >= 4 && n <= 9 ? 'pass' : 'warn', `${n} word${n === 1 ? '' : 's'}`, 'Four to nine words is usually enough to be specific without rambling.');

  // 6. Specificity (10): a number, a question, or a personalization token.
  const spec = [];
  if (/\d/.test(s)) spec.push('a number');
  if (/\?/.test(s)) spec.push('a question');
  if (/\{\{\s*\w+\s*\}\}|\*\|\w+\|\*|\[\s*(?:first ?name|name|company)\s*\]|%\w+%/i.test(s)) spec.push('personalization');
  add(spec.length ? 10 : 4, 10, spec.length ? 'pass' : 'warn', spec.length ? `Specific: uses ${spec.join(', ')}` : 'Could be more specific', spec.length ? 'Specific details tell readers what they’ll get.' : 'A number, a name or a clear question often makes a subject more concrete.');

  // 7. Emoji (5)
  const emoji = (s.match(EMOJI) || []).length;
  add(emoji <= 1 ? 5 : 0, 5, emoji <= 1 ? 'pass' : 'warn', `${emoji} emoji`, emoji <= 1 ? 'Fine. Check your brand voice allows it.' : 'More than one emoji can look like a promo blast and may not render everywhere.');

  // 8. Misleading prefixes (penalty)
  if (/^\s*(re|fwd?)\s*:/i.test(s)) { score -= 15; rows.push({ status: 'fail', label: 'Starts with "Re:" or "Fwd:" (−15)', detail: 'Fake reply prefixes break trust and can break anti-spam law in some markets.' }); }

  // 9. Preheader (5)
  const pre = preheader.trim();
  if (!pre) add(0, 5, 'warn', 'No preview text', 'Without it, inboxes show the first line of the email instead. Write one.');
  else if (pre.toLowerCase() === lower) add(0, 5, 'warn', 'Preview text repeats the subject', 'Use it to add a second reason to open.');
  else add(5, 5, 'pass', `Preview text: ${[...pre].length} characters`, 'It adds context next to the subject.');

  return { subject: s, preheader: pre, score: Math.max(0, Math.min(100, score)), rows };
}

tool('email-subject-line-analyzer', {
  run: (v) => analyze(v.subject, v.preheader),
  render(r, out) {
    const verdict = r.score >= 80 ? 'Strong' : r.score >= 60 ? 'Good, with fixes' : 'Needs work';
    out.append(
      section('Score',
        el('div', { class: 'td-tool__score' }, el('strong', { text: num(r.score) }), el('span', { text: `/ 100 · ${verdict}` })),
        el('div', { class: 'td-tool__meter', role: 'img', 'aria-label': `Score ${num(r.score)} out of 100` }, el('span', { style: `width:${r.score}%` })),
        el('p', { class: 'td-tool__p', text: 'The score is our rubric, not a prediction of open rates. Test real subject lines against each other with your own list.' })),
      section('Inbox preview',
        el('div', { class: 'td-tool__mock', style: 'width:min(100%,340px)' }, el('span', { class: 'td-tool__mock-label', text: 'Phone (approximate)' }), el('span', { class: 'td-tool__mock-subject', text: r.subject }), el('span', { class: 'td-tool__mock-pre', text: r.preheader || 'First line of your email…' }))),
      section('How we scored it', checks(r.rows)),
    );
  },
  summary: (r) => `Subject "${r.subject}" scored ${r.score}/100.`,
});
