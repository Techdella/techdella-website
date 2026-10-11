---
title: "What Is Alt Text? How to Write It, With Examples"
slug: alt-text
excerpt: "Alt text describes an image for people who can't see it and for search engines. Here is how to write it well, when to leave it empty, and examples to copy."
primary_kw: alt text
secondary_kws: [image alt text, alt attribute, image seo, accessibility]
intent: informational
audience: "Marketers, writers and designers who publish images on websites and social media"
short_definition: "Alt text (alternative text) is a short written description of an image, added in the HTML alt attribute, that screen readers read aloud and search engines use to understand the image when it can't be seen."
taxonomies:
  glossary_category: [seo]
related_terms: [technical-seo, schema-markup, mobile-optimization, landing-page]
related_service: technical-seo
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
internal_links:
  - /services/technical-seo/
  - /glossary/technical-seo/
  - /glossary/schema-markup/
  - /tools/on-page-seo-checker/
rank_math_description: "Alt text explained: why it matters for accessibility and image SEO, good and bad examples, when to use empty alt, and the most common mistakes in {{year}}."
---

Alt text is the written description attached to an image on a web page. If someone uses a screen reader, they hear the alt text instead of seeing the image. If the image fails to load, the alt text appears in its place. Search engines read it too, as one of the main clues to what an image shows. Good alt text is short, specific and describes what matters about the image in its context.

:::takeaways
- Alt text serves people first: screen reader users rely on it to understand images.
- Google calls alt text the most important attribute for giving it image metadata.
- Describe what the image shows and why it's there. Don't stuff keywords.
- Decorative images should have an empty alt attribute (alt=""), not a missing one.
:::

## Why alt text matters

It's an accessibility requirement first. The Web Content Accessibility Guidelines (WCAG) require a text alternative for non-text content, and missing alt text remains one of the most common failures on the web.

:::stats "Missing alt text is still widespread" highlight=0
53.1% | of the top 1,000,000 home pages had images with missing alt text | WebAIM Million, 2026 | https://webaim.org/projects/million/
16.2% | of all home page images had missing alt text | WebAIM Million, 2026 | https://webaim.org/projects/million/
45% | of images missing alt text were links, leaving the link with no description | WebAIM Million, 2026 | https://webaim.org/projects/million/
:::

It also helps search. Google's [image SEO guidance](https://developers.google.com/search/docs/appearance/google-images) says it uses alt text, along with computer vision and page content, to understand what an image shows. When an image is a link, its alt text works like anchor text.

## Good and bad alt text, side by side

| Image | Weak alt text | Better alt text |
|---|---|---|
| Team photo on About page | "image1.jpg" | "The TechDella team planning a campaign around a whiteboard" |
| Product dashboard screenshot | "SEO software SEO tool best SEO dashboard" | "Dashboard showing organic clicks rising over six months" |
| Chart in a report | "chart" | "Bar chart of leads by channel, with email the largest source" (with the full data in the text) |
| Decorative divider line | "line" | alt="" (empty, so screen readers skip it) |
| Linked logo in the header | "logo" | "TechDella home" |

The pattern: describe the content and the purpose, not the file. For a linked image, describe where the link goes.

:::myth-fact
Myth: Alt text is mainly an SEO tactic, so include your keyword.
Fact: Alt text is for people who can't see the image. Google warns against keyword stuffing in alt attributes. Use a keyword only where it naturally describes the image.
Myth: Every image needs a long description.
Fact: Most alt text should be one short sentence. Decorative images need an empty alt. Complex charts need a short alt plus the data in the surrounding text or a table.
:::

## Example from our work

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to write alt text

:::steps "Write useful alt text in under a minute"
1. Ask why the image is there | If removing it loses nothing, it's decorative. Use alt="".
2. Describe what matters | Say what the image shows that's relevant to the page: people, action, data, or text in the image.
3. Keep it short | Aim for one sentence. Screen readers read it in full, so avoid filler.
4. Skip "image of" | Screen readers already announce it's an image.
5. Include text shown in the image | If the image contains words, such as a banner or quote, put those words in the alt text.
6. Check it in context | Read the paragraph around the image, then the alt text. It should fill the gap, not repeat the caption.
:::

:::cta "Want an accessibility and image check on your key pages?" "See our technical SEO service" /services/technical-seo/
We find missing alt text, oversized images and other issues that hurt both users and rankings.
:::

### Alt text on social media

LinkedIn, Instagram, X and Facebook all let you add alt text to images. The same rules apply: describe the image for someone who can't see it. It takes seconds and makes your posts usable for more of your audience.

Our [on-page SEO checker](/tools/on-page-seo-checker/) flags images without alt text on any page. Alt text is part of a sound [technical SEO](/glossary/technical-seo/) setup, alongside [schema markup](/glossary/schema-markup/) for describing content machines can't see.

## Related terms

- [Technical SEO](/glossary/technical-seo/): image handling is part of a healthy site.
- [Schema markup](/glossary/schema-markup/): another way to label content for machines.
- [Mobile optimization](/glossary/mobile-optimization/): images also need to load fast on phones.
- [Landing page](/glossary/landing-page/): where descriptive images support conversion.

:::faq
### How long should alt text be?
Usually one short sentence, often under about 125 characters, although there's no strict limit in HTML. Some screen readers and tools handle long alt text poorly. If an image needs a longer explanation, such as a detailed chart, put that in the page text or a caption.
### What is the difference between alt text and an image caption?
A caption is visible to everyone and usually adds context or credit. Alt text replaces the image for people who can't see it. They shouldn't be identical. If the caption already describes the image fully, keep the alt text brief.
### Does alt text help SEO?
Yes. Google uses it to understand images, which helps them appear in Google Images and gives context to the page. Its biggest value is still accessibility, and accessible pages tend to be clearer for everyone.
### Should every image have alt text?
Every image should have an alt attribute. Meaningful images need a description; purely decorative images should use an empty alt (alt="") so screen readers skip them. A missing alt attribute is the problem, because some screen readers then read the file name.
:::

## How TechDella can help

Our [technical SEO service](/services/technical-seo/) includes an image and accessibility audit, with fixes built into your templates.
