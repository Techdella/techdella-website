// Generic block editor for TechDella server-rendered blocks (inc/blocks.php).
// Sidebar controls are generated from each block.json's attributes; the canvas
// previews the real render.php through ServerSideRender. Hints come from the
// "techdella.controls" key in block.json (exposed as window.TD_BLOCKS):
//   "textarea" | "media" (image) | "media:video" | "media:text" | "json" | "url" | "select" (uses the attribute enum)
// Blocks whose render.php prints $content (e.g. the hero's right-hand slot)
// opt in with "techdella.innerBlocks": true; they get an InnerBlocks area and
// save their inner blocks, so editing never drops a mock passed by a pattern.
const { registerBlockType, getBlockType } = wp.blocks;
const { createElement: el, Fragment, useState } = wp.element;
const { InspectorControls, InnerBlocks, useBlockProps, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { PanelBody, TextControl, TextareaControl, ToggleControl, SelectControl, Button, Notice } = wp.components;
const ServerSideRender = wp.serverSideRender;

const META = window.TD_BLOCKS || {};

const label = (key) => key.replace(/([A-Z])/g, ' $1').replace(/[_-]/g, ' ').replace(/^./, (c) => c.toUpperCase());

function JsonControl({ name, value, onChange }) {
  const [text, setText] = useState(() => JSON.stringify(value ?? [], null, 2));
  const [error, setError] = useState('');
  return el(
    Fragment,
    null,
    el(TextareaControl, {
      label: `${label(name)} (JSON)`,
      value: text,
      rows: 10,
      onChange: (next) => {
        setText(next);
        try { onChange(JSON.parse(next)); setError(''); } catch (e) { setError(e.message); }
      },
    }),
    error ? el(Notice, { status: 'error', isDismissible: false }, error) : null,
  );
}

function control(name, schema, hint, value, set) {
  const onChange = (v) => set({ [name]: v });
  if (hint && hint.startsWith('media')) {
    // "media" = image; "media:video" / "media:text" (captions) pick other file types.
    const type = hint.split(':')[1] || 'image';
    const noun = { image: 'image', video: 'video', text: 'file' }[type] || 'file';
    return el(
      MediaUploadCheck,
      { key: name },
      el(MediaUpload, {
        onSelect: (m) => onChange(m.id),
        allowedTypes: [type],
        value,
        render: ({ open }) => el('div', { style: { marginBottom: '16px' } },
          el('p', { style: { margin: '0 0 6px' } }, label(name)),
          el(Button, { variant: 'secondary', onClick: open }, value ? `Replace ${noun} (#${value})` : `Choose ${noun}`),
          value ? el(Button, { variant: 'link', isDestructive: true, onClick: () => onChange(0) }, 'Remove') : null),
      }),
    );
  }
  if (schema.type === 'boolean') return el(ToggleControl, { key: name, label: label(name), checked: !!value, onChange });
  if (schema.enum) {
    return el(SelectControl, { key: name, label: label(name), value, options: schema.enum.map((v) => ({ label: String(v), value: v })), onChange });
  }
  if (schema.type === 'array' || schema.type === 'object' || hint === 'json') {
    return el(JsonControl, { key: name, name, value, onChange });
  }
  if (schema.type === 'number' || schema.type === 'integer') {
    return el(TextControl, { key: name, type: 'number', label: label(name), value: value ?? '', onChange: (v) => onChange(v === '' ? undefined : Number(v)) });
  }
  if (hint === 'textarea') return el(TextareaControl, { key: name, label: label(name), value: value ?? '', onChange });
  return el(TextControl, { key: name, type: hint === 'url' ? 'url' : 'text', label: label(name), value: value ?? '', onChange });
}

Object.keys(META).forEach((name) => {
  const hints = META[name].controls || {};
  const hasInner = !!META[name].innerBlocks;
  registerBlockType(name, {
    edit({ attributes, setAttributes }) {
      const type = getBlockType(name);
      const schema = (type && type.attributes) || {};
      const fields = Object.keys(schema).filter((k) => !['lock', 'metadata', 'className', 'style'].includes(k) && hints[k] !== 'hidden');
      return el(
        'div',
        useBlockProps(),
        el(
          InspectorControls,
          null,
          el(PanelBody, { title: 'Content', initialOpen: true },
            META[name].help ? el('p', { className: 'td-editor-help' }, META[name].help) : null,
            fields.map((k) => control(k, schema[k], hints[k], attributes[k], setAttributes))),
        ),
        el(ServerSideRender, { block: name, attributes, httpMethod: 'POST' }),
        hasInner ? el('div', { className: 'td-editor-slot' },
          el('p', { className: 'td-editor-help' }, META[name].slotLabel || 'Inner blocks (right-hand slot)'),
          el(InnerBlocks, { allowedBlocks: META[name].allowedBlocks || undefined })) : null,
      );
    },
    // Dynamic blocks: render.php renders the markup; only inner blocks are saved.
    save: hasInner ? () => el(InnerBlocks.Content) : () => null,
  });
});
