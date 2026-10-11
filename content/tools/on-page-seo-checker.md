---
title: "On-Page SEO Checker"
slug: on-page-seo-checker
excerpt: "A free on-page SEO checker: enter a URL to audit its title, meta description, headings, indexing, canonical, alt text, schema, links and AI crawler access, with a fix for each issue."
rank_math_description: "Free on-page SEO checker: audit any URL's title, meta description, H1, canonical, alt text, schema, links and AI crawler access, with a clear fix for each."
tool_component: on-page-seo-checker
primary_kw: on-page seo checker
secondary_kws: [on page seo audit, seo page analyzer, meta tag checker, h1 checker, ai crawler robots.txt checker]
intent: transactional
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
gate: email
ad_slots: true
internal_links:
  - /services/seo/
  - /services/technical-seo/
  - /glossary/alt-text/
  - /glossary/schema-markup/
  - /glossary/llms-txt/
  - journal:/b2b-seo-strategy/
how_to:
  - "Enter a public page URL | Any page that loads without a login. Paste the full address, such as https://example.com/services/. Our server fetches it once and follows up to 3 redirects."
  - "Add a target keyword, if you have one | The tool then checks whether that exact phrase appears in the title, the H1, the meta description and the first 100 words."
  - "Click Check this page | It takes up to 10 seconds. The first time, enter your email to unlock the results."
  - "Read the summary | Checks passed as a percentage, the HTTP status, how long the fetch took and the word count."
  - "Work through Fix, then Improve | Each of the 13 on-page checks is marked Good, Improve, Fix or Note, with the reason and what to change."
  - "Rerun after 10 minutes | Results for a URL are cached for 10 minutes, so wait before you check your fixes."
faq:
  - "What does the on-page SEO checker look at? | Thirteen on-page checks: title length, meta description length, number of H1s, noindex, canonical, HTTPS, word count, image alt attributes, structured data, mobile viewport, html lang, Open Graph title and image, and internal links. It also checks your target keyword, robots.txt rules for 9 AI crawlers, whether llms.txt exists, and shows your heading outline."
  - "How is the percentage worked out? | It is the share of on-page checks marked Good, out of all checks except Notes. Usually that is 13 checks, so each one is worth about 8 points. A canonical that points to another URL is marked as a Note and left out. The keyword checks and AI readiness checks don't count toward the percentage."
  - "Why can't the checker fetch my page? | The page must be public, answer within 5 seconds, return HTML under 2 MB and redirect no more than 3 times. Pages behind a login, on private networks, on unusual ports, or that return an error status like 404 or 500 can't be checked. Firewalls that block unknown visitors can also stop the fetch."
  - "Does a 100% score mean my page will rank? | No. These checks cover the basics that help search engines understand a page. Rankings depend mostly on how well the page answers the query, how trusted the site is, and links from other sites. A page can pass every check and still not rank for a competitive keyword."
  - "What length should my title tag and meta description be? | The tool passes titles of 30 to 60 characters and meta descriptions of 120 to 160 characters. Google publishes no hard limit for either, but it truncates both to fit the device width. Those ranges keep the important words visible. For descriptions we aim for 140 to 155 characters."
  - "Why is the word count higher than my article? | The checker counts all visible text in the page body after removing scripts, styles and SVGs. That includes your menu, sidebar and footer. Use it as a rough signal of a thin page, not an exact article length."
  - "What does the AI crawler table mean? | It reads your robots.txt and shows whether GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, Claude-SearchBot, PerplexityBot, Google-Extended, Applebot-Extended and CCBot are allowed, blocked or partly blocked. Some are search bots that can cite you in answers; others collect training data. Blocking is a business choice, but block deliberately."
---

The On-Page SEO Checker audits one URL against the on-page basics that search engines and AI answer engines rely on: the title tag, meta description, H1, indexing, canonical, HTTPS, word count, image alt text, structured data, mobile viewport, language, Open Graph tags and internal links. It also checks your target keyword and which AI crawlers your robots.txt lets in. It is a free on-page SEO checker; the first time you use it, we ask for your email to show the results.

It is for founders, marketers and developers who want a quick, honest read on a single page before or after publishing. Every check comes with the reason and the fix.

:::takeaways
- Fix the red rows first: noindex, a missing title, no H1, missing alt attributes or a missing viewport tag.
- One H1 per page, a 30 to 60 character title and a 120 to 160 character meta description cover most quick wins.
- A self-referencing canonical stops UTM and tracking links from splitting your signals.
- Check the AI crawler table. Blocking search bots by accident can keep you out of AI answers.
- A perfect score is a baseline, not a ranking. Content and trust still decide competitive queries.
:::

## How the on-page SEO checker works

When you click **Check this page**, our server fetches the URL once. The fetch is guarded: it only visits public addresses on standard web ports, follows at most 3 redirects, gives up after 5 seconds and reads at most 2 MB. It also requests your site's robots.txt and llms.txt. We don't store the page. Results for the same URL are cached for 10 minutes, and each visitor can run 10 checks a minute and 100 a day.

The server extracts plain facts from the HTML. Your browser then turns those facts into checks.

| Check | Good | Improve | Fix |
|---|---|---|---|
| Title tag | 30 to 60 characters | Shorter or longer | No title |
| Meta description | 120 to 160 characters | Shorter or longer | None |
| H1 headings | Exactly one | Two or more | None |
| Indexing | No noindex in meta robots or X-Robots-Tag | | noindex found |
| Canonical | Points to this URL | Missing (one that points elsewhere shows as a Note) | |
| HTTPS | Final URL is https | | http |
| Word count | 300 or more | Under 300 | |
| Image alt attributes | Every img has an alt attribute | | Any img without one |
| Structured data | JSON-LD or microdata found | None found | |
| Mobile viewport | Viewport meta tag present | | Missing |
| Language | lang attribute on html | Missing | |
| Open Graph | og:title and og:image both set | Either missing | |
| Internal links | 3 or more | Fewer than 3 | |

Two details are worth knowing. An image with an empty alt attribute (`alt=""`) passes, because that is the correct way to mark a decorative image; only images with no alt attribute at all fail. And the word count includes your menu and footer text, so a thin article inside a big template can still pass.

**Target keyword.** If you enter one, the tool looks for that exact phrase, ignoring case, in the title, the H1, the meta description and the first 100 words of visible text. A miss in the title, H1 or first 100 words is marked Fix; a miss in the meta description is marked Improve. On many sites the first 100 words include the menu, so a miss there may just mean your intro starts lower down.

**AI search readiness.** The tool reports whether robots.txt and [llms.txt](/glossary/llms-txt/) exist, and reads your robots.txt rules for nine AI crawlers: GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, Claude-SearchBot, PerplexityBot, Google-Extended, Applebot-Extended and CCBot. Each shows as Allowed, Blocked or Partly blocked. These rows are Notes and don't change your percentage.

You also get your heading outline, the first 30 headings indented by level, which is the fastest way to spot a skipped level or an empty heading.

## How to read your results

The headline number is the share of on-page checks marked Good. With 13 scored checks, each one is worth about 8 percentage points.

| Checks passed | What it usually means | What to do |
|---|---|---|
| 100% | The basics are in place | Spend your time on the content and on links |
| 85% to 92% | One or two gaps, often Open Graph or a missing canonical | Quick fixes; most take minutes in your CMS or SEO plugin |
| 70% to 84% | Several gaps, often title length, meta description and H1 | Fix these before promoting the page |
| Under 70% | Template-level problems | Check your theme or CMS; the same issues are likely on every page |

Look at the Fix rows before the percentage. One noindex makes every other check irrelevant, because the page can't appear in search at all.

## A worked example

Here is a typical service page we might check, with the target keyword "B2B SEO services":

- Title: "B2B SEO Services for SaaS, Fintech and eCommerce Companies in 2026" is 66 characters, marked Improve.
- Meta description: none, marked Fix.
- H1: two, because the theme wraps the logo in an H1 as well as the page title, marked Improve.
- The other 10 on-page checks pass.

That is 10 Good out of 13 scored checks: 10 / 13 = 0.769, which rounds to **77%**.

The fixes: cut the title to "B2B SEO Services for SaaS and Fintech" (37 characters), write a 150-character meta description, and change the logo from an H1 to a plain link. Now 13 out of 13 pass: **100%**. Each fix moved the score by about 8 points, and none of them touched the content.

The keyword section would then show the phrase in the title and H1, and point out whether it also appears in the description and the opening paragraph.

## What Google says about titles and descriptions

:::stats "How Google uses your title tag"
87% | of the time, Google uses the HTML title element as the title link in results, up from around 80% before a 2021 change | Google Search Central Blog, 2021 | https://developers.google.com/search/blog/2021/09/more-info-about-titles
:::

Google's own documentation says there is no limit on how long a title element or a meta description can be, but that both are truncated in results as needed, typically to fit the device width. It asks for descriptive and concise titles, and says it uses your meta description for the snippet when it describes the page more accurately than text taken from the page. See Google's guides to [title links](https://developers.google.com/search/docs/appearance/title-link) and [snippets](https://developers.google.com/search/docs/appearance/snippet).

So the checker's ranges are not Google rules. They are practical ranges that keep your key words visible.

## Best practices

:::checklist "On-page basics for every page"
- One H1 that says what the page is about, with the main keyword in natural words.
- A unique title with the keyword near the start and the brand at the end.
- A meta description written for the click: what the reader gets, in about 150 characters.
- An answer-first opening paragraph: two or three sentences that answer the query directly.
- Alt text that describes what each meaningful image shows; empty alt for decorative images. See our [alt text guide](/glossary/alt-text/).
- JSON-LD [schema markup](/glossary/schema-markup/) for the page type: Organization and WebPage, plus Service, Article or Product where it fits.
- A self-referencing canonical, so tracking parameters point back to the clean URL.
- At least three internal links to closely related pages, with descriptive anchor text.
- A deliberate robots.txt policy for AI crawlers.
:::

:::cta "Want the whole site checked, not one page?" "See our SEO service" /services/seo/
We audit every template, fix the technical issues and build content that earns rankings and citations.
:::

## Common mistakes

:::myth-fact
Myth: You must keep titles under exactly 60 characters or Google will penalize you.
Fact: Google says there is no length limit. Long titles are simply cut off to fit the screen. Keep the important words first and avoid stuffing; the length range is about readability, not a penalty.
:::

:::myth-fact
Myth: Adding schema markup will boost rankings.
Fact: Structured data helps search engines understand a page and can make it eligible for some rich results. It doesn't replace helpful content, and markup that doesn't match what's on the page can make it ineligible.
:::

:::myth-fact
Myth: Blocking every AI bot in robots.txt protects your content with no downside.
Fact: Some of these crawlers fetch pages so AI search products can cite them, while others collect training data. Blocking all of them can keep you out of AI answers. Decide bot by bot.
:::

Other mistakes we see often:

- **Two H1s from the theme.** Logos, sliders and widget titles are common culprits. Check the heading outline.
- **A noindex left over from staging.** It is the single most damaging line on a page, and it often survives a launch.
- **The same title and description on every page.** Usually a CMS default. Each page needs its own.
- **Canonical pointing to the home page.** Some plugins do this by mistake, telling Google every page is a duplicate.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## What the checker can't see

The checker reads one page's HTML as our server receives it. It does not run JavaScript, so content that only appears after scripts run won't be counted. It can't judge whether your content is the best answer for the query, and it doesn't see backlinks, brand mentions or field page speed. For speed, use PageSpeed Insights.

For crawl errors, redirect chains and site-wide issues, you need a full crawl. Our [technical SEO service](/services/technical-seo/) covers that, and our [B2B SEO strategy guide](journal:/b2b-seo-strategy/) shows where on-page work fits in a plan that leads to pipeline.
