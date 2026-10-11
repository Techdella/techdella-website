// Persona Builder (§12.1). The server route calls the model API with the key
// from AI_API_KEY; if it isn't configured the route says so and we show that
// message. We never fill in sample output on the client.
import { tool, el, section, copyButton } from '../tool-kit/kit.js';

const LISTS = [
  ['goals', 'Goals'],
  ['pains', 'Pains'],
  ['objections', 'Likely objections'],
  ['channels', 'Where they look for answers'],
  ['buying_triggers', 'Buying triggers'],
  ['messages', 'Message angles to test'],
];

const asText = (p) => [
  `${p.persona_name} (${p.role})`,
  p.summary,
  ...LISTS.flatMap(([k, t]) => ['', `${t}:`, ...(p[k] || []).map((x) => `- ${x}`)]),
].join('\n');

tool('persona-builder', {
  busyLabel: 'Building your persona…',
  async run(v, ctx) {
    const res = await ctx.api('tools/ai', {
      tool: 'persona-builder',
      input: { product: v.product, audience: v.audience, industry: v.industry || '', size: v.size || '', region: v.region || '', notes: v.notes || '' },
    });
    return res.data;
  },
  summary: (p) => `${p.persona_name} (${p.role}): ${p.summary}`,
  render(p, out) {
    out.append(
      section(p.persona_name,
        el('p', { class: 'td-tool__lead', text: p.role }),
        el('p', { text: p.summary })),
      ...LISTS.filter(([k]) => (p[k] || []).length).map(([k, t]) => section(t,
        el('ul', { class: 'td-tool__list' }, p[k].map((x) => el('li', { text: x }))))),
      el('p', { class: 'td-tool__note', text: 'AI-generated. Treat this as a working hypothesis and check it against real customer calls.' }),
      copyButton('Copy persona', () => asText(p)),
    );
  },
});
