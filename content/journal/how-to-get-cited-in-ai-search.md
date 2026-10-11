---
title: "How to Get Cited in AI Search (Google, ChatGPT, Perplexity)"
slug: how-to-get-cited-in-ai-search
excerpt: "What actually gets a brand cited in Google AI Overviews, ChatGPT search and Perplexity: crawler access, answer-first pages, consistent entity facts and third-party mentions. Plus an honest look at llms.txt and how to measure share of answers."
primary_kw: how to get cited in ai search
secondary_kws: [ai overviews citations, get cited by chatgpt, perplexity citations, llms.txt, ai search visibility, share of answers]
intent: informational
audience: "B2B founders and marketing leads who want their brand mentioned and linked in AI answers"
angle: "Separates what the platforms themselves document (crawler controls, Google's no-special-requirements stance) from folklore, and gives a measurement method with plain arithmetic."
proof: "Google Search Central, OpenAI and Perplexity crawler docs; Alphabet, Pew and Ahrefs data."
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
categories: [ai-search]
cta_service: ai-search-optimization
taxonomies:
  td_service_tag: [ai-search-optimization]
tldr: "To get cited in AI search, make sure AI search crawlers can reach your pages, publish clear answer-first passages backed by sources, keep your company facts identical everywhere, and earn mentions on sites the engines already trust. No file or schema guarantees a citation, so measure your share of answers over a fixed set of prompts."
rank_math_description: "How to get cited in AI search: crawler access, answer-first pages, entity consistency, schema, the truth about llms.txt and measuring share of answers."
internal_links:
  - /services/ai-search-optimization/
  - /services/technical-seo/
  - /glossary/schema-markup/
  - /glossary/technical-seo/
  - /glossary/backlinks/
  - /b2b-seo-strategy/
---

To get cited in AI search, you need four things: AI search crawlers that can reach your pages, passages that answer a question directly with evidence, company facts that match everywhere they appear, and mentions on sites the engines already trust. Google AI Overviews, ChatGPT search and Perplexity all build answers from web pages they can retrieve. Your job is to be the clearest, most credible page on the question, and to be easy to retrieve.

There's no shortcut file, no special markup and no paid placement that guarantees a citation. Anyone who promises one is guessing. What you can do is remove every reason an engine has to skip you, and then measure whether it's working.

:::takeaways
- Check robots.txt first: blocking OAI-SearchBot or PerplexityBot keeps you out of those engines' answers.
- Google says AI Overviews and AI Mode need no special optimization beyond SEO fundamentals.
- Write answer-first: a two-to-three sentence answer under each question heading, then the detail.
- Make your company facts identical on your site, profiles and directories.
- llms.txt is a proposal, not a standard. Google says you don't need it.
- Measure share of answers across a fixed prompt set, monthly. Single answers vary too much to judge.
:::

## Why AI citations matter for B2B brands

AI answers now sit in front of a large share of research, and they absorb clicks that used to go to websites.

:::stats "How big AI search has become" highlight=0
2 billion+ | monthly users of Google AI Overviews | Alphabet Q2 2025 earnings | https://blog.google/inside-google/message-ceo/alphabet-earnings-q2-2025/
100 million+ | monthly active users of AI Mode in the US and India | Alphabet Q2 2025 earnings | https://blog.google/inside-google/message-ceo/alphabet-earnings-q2-2025/
94% | of B2B buyers use LLMs in their buying process | 6sense Buyer Experience Report, 2025 | https://6sense.com/report/buyer-experience/
1% | of visits with an AI summary included a click on a link inside the summary | Pew Research Center, 2025 | https://www.pewresearch.org/short-reads/2025/07/22/google-users-are-less-likely-to-click-on-links-when-an-ai-summary-appears-in-the-results/
:::

The click problem is real. Ahrefs compared click-through rates for the top-ranking page on keywords with and without an AI Overview:

:::chart "Position-one desktop click-through rate, December 2025" unit=% max=5 highlight=1
Informational keywords without an AI Overview | 3.9
Keywords with an AI Overview | 1.6
source: Ahrefs, February 2026 | https://ahrefs.com/blog/ai-overviews-reduce-clicks-update/
:::

Ahrefs puts that gap at a 58% lower click-through rate where an AI Overview appears. It's a correlation across 300,000 keywords, not proof of cause, but the direction matches Pew's browsing data. So if a buyer reads the answer and doesn't click, the only way your brand reaches them is by being named in it.

## How AI search engines choose what to cite

Each engine works differently, but all of them retrieve pages at the moment someone asks, then write an answer from what they retrieved. That means the basics of being crawlable and indexable come first.

| Engine | What it retrieves from | Crawler to allow | What its owner says |
|---|---|---|---|
| Google AI Overviews and AI Mode | Google's search index | Googlebot | A page must be indexed and eligible to show with a snippet; no extra requirements ([Google](https://developers.google.com/search/docs/appearance/ai-features)) |
| ChatGPT search | OpenAI's search index plus live page visits | OAI-SearchBot (search), ChatGPT-User (user-triggered visits) | OAI-SearchBot is for search; GPTBot is for training and is controlled separately ([OpenAI](https://developers.openai.com/api/docs/bots)) |
| Perplexity | Perplexity's index plus live page visits | PerplexityBot, Perplexity-User | PerplexityBot surfaces and links sites in results; it isn't used for model training ([Perplexity](https://docs.perplexity.ai/guides/bots)) |

Two things follow from that table. First, you can block training crawlers without leaving AI search. Disallowing GPTBot doesn't remove you from ChatGPT search, and Google-Extended "does not impact a site's inclusion in Google Search" ([Google](https://developers.google.com/search/docs/crawling-indexing/google-common-crawlers)). Second, if you block the search crawlers, by accident or through an over-eager firewall rule, no amount of content work will get you cited.

## How to get cited in AI search: seven steps

:::steps "From invisible to cited"
1. Open the door to search crawlers | Allow Googlebot, OAI-SearchBot and PerplexityBot in robots.txt and in your firewall or CDN bot settings. Then check server logs to confirm they actually visit.
2. Make the text server-rendered | Key answers should be in the HTML the server sends, not loaded by JavaScript afterward. Test by viewing the page source.
3. Pick the questions that lead to your offer | List 20–30 questions buyers ask before they buy. These become your prompt set and your content plan.
4. Answer first, then explain | Under each question heading, give a direct two-to-three sentence answer. Follow with steps, numbers, examples and sources.
5. Back claims with evidence | Name the source, the year and the number. Link to the original report, not a blog that quotes it.
6. Make your entity facts consistent | Same company name, description, services, locations and founder details on your site, LinkedIn, directories and partner pages.
7. Earn third-party mentions | Get described accurately on sites the engines already cite for your topic: industry publications, review sites, partner directories, communities.
:::

### Steps 1–2: Access and rendering

This is [technical SEO](marketing:/glossary/technical-seo/) applied to a new set of visitors. The common failures we see on B2B sites:

- A CDN or security plugin blocks unknown bots by default, including OAI-SearchBot and PerplexityBot.
- Pricing, FAQs or feature tables load through JavaScript, so a crawler that doesn't run scripts sees an empty section.
- Key pages carry a stray `noindex` from a staging copy, which also removes them from Google's AI features.

### Steps 3–5: Pages that are easy to quote

AI answers are built from passages, not whole pages. A passage gets used when it answers the question on its own, in plain words, with something checkable in it. Write each section so it could be lifted out and still make sense:

- **Use the question as the heading.** "How much does a B2B SEO retainer cost?" beats "Pricing considerations."
- **Put the answer in the first sentence under it.** Then add the conditions, the numbers and the exceptions.
- **Include specifics.** A number with its source, a named standard, a defined term. Vague sentences have nothing to cite.
- **Use real tables and lists.** Comparisons in HTML tables are easier to parse than comparisons buried in paragraphs.
- **Date your content and keep it current.** Show "last reviewed" and update numbers when new data comes out.

This is the same answer-first structure that helps with featured snippets. If you're building the underlying content plan, our [B2B SEO strategy guide](/b2b-seo-strategy/) shows how to map questions to buying stages.

### Step 6: Entity consistency

An entity is a thing an engine can identify: your company, your founder, your product. Engines are more confident citing you when every source describes you the same way. Run this check once a quarter:

:::checklist "Entity consistency check"
- Company name is spelled the same everywhere, including capitalization
- One-sentence description of what you do matches across site, LinkedIn and directories
- Services are named the way buyers name them, and the same way on every profile
- Founder and leadership names and titles match your About page
- Locations and markets served match; no old addresses
- Organization schema on your site lists your real social profiles in sameAs
- Partner and directory listings link to the right page, not an old URL
:::

### Step 7: Mentions on trusted sites

Pew found that Wikipedia, YouTube and Reddit were the sources cited most often in Google's AI summaries in its study ([Pew Research Center, 2025](https://www.pewresearch.org/short-reads/2025/07/22/google-users-are-less-likely-to-click-on-links-when-an-ai-summary-appears-in-the-results/)). You don't need to game those sites. The lesson is that engines lean on sources outside your own domain. For B2B, the useful ones are usually industry publications, analyst and review sites, partner directories and podcasts. Classic [backlinks](marketing:/glossary/backlinks/) work the same way: relevance beats volume.

:::cta "Want to know where your brand shows up in AI answers?" "See AI search optimization" marketing:/services/ai-search-optimization/
We check your crawler access, test a fixed set of buyer prompts across the main engines, and fix the pages and profiles that keep you out of the answer.
:::

## Does schema markup help you get cited?

[Schema markup](marketing:/glossary/schema-markup/) helps engines read facts about a page: who published it, what it's about, who the author is. It's worth doing well. But be precise about what it does and doesn't do.

- **Google says no special schema is needed** for AI Overviews or AI Mode, and that structured data should match the visible text on the page ([Google Search Central](https://developers.google.com/search/docs/appearance/ai-features)).
- **Organization and Person markup** with accurate sameAs links supports the entity consistency in step 6.
- **Article markup** with author, dates and publisher makes freshness and authorship explicit.
- **FAQ markup won't get you FAQ rich results** on most sites. Google limited those to well-known government and health sites in 2023 ([Google Search Central Blog](https://developers.google.com/search/blog/2023/08/howto-faq-changes)). Keep FAQs as on-page content.

Schema is a clarity layer, not a citation trigger. If the passage isn't the best answer, markup won't change that.

## What about llms.txt?

llms.txt is a proposal from Jeremy Howard, first published in September 2024, for a Markdown file at your site root that gives AI tools a short summary of the site and a list of key links ([llmstxt.org](https://llmstxt.org/)). The idea is reasonable: give agents a clean map instead of making them parse menus and scripts.

Here's the honest position:

- **It isn't a standard.** No major AI search engine has documented that it uses llms.txt to choose citations.
- **Google says you don't need it.** Its guidance for AI features says: "You don't need to create new machine readable files, AI text files, or markup to appear in these features" ([Google Search Central](https://developers.google.com/search/docs/appearance/ai-features)).
- **It's cheap.** If your CMS can generate one from your real pages, it takes minutes and does no harm. Keep it accurate and in sync with your site.

So add one if it's easy, but don't spend budget on it, and don't believe anyone who reports citation gains from llms.txt alone.

:::myth-fact
Myth: Adding an llms.txt file will get you cited by ChatGPT and Google.
Fact: llms.txt is an unofficial proposal. Google says no new machine-readable files are needed for its AI features, and no major engine documents using it for citations.
Myth: Blocking GPTBot removes you from ChatGPT search.
Fact: OpenAI controls search with OAI-SearchBot and training with GPTBot. You can opt out of training and still appear in ChatGPT search.
Myth: You need special schema to appear in AI Overviews.
Fact: Google says there is no special schema.org markup required. The page must be indexed and eligible for a snippet.
Myth: If you rank first on Google, AI answers will cite you.
Fact: Ranking helps, especially for AI Overviews, but each engine retrieves and selects passages its own way. Measure each engine separately.
:::

## How to measure share of answers

AI answers change from one run to the next, so a single screenshot proves nothing. Measure the trend over a fixed prompt set instead.

:::steps "A monthly share-of-answers check"
1. Fix the prompt set | 20–30 real buyer questions, written the way buyers type them. Don't change them month to month.
2. Fix the engines | For example Google AI Overviews or AI Mode, ChatGPT search and Perplexity.
3. Run each prompt more than once | Two or three runs per engine, logged out where possible, same location settings.
4. Record two things per answer | Was your brand mentioned? Was your site linked as a source?
5. Calculate the rates | Mentions divided by total answers, and citations divided by total answers.
6. Compare with competitors | Track the same rates for three named competitors on the same prompts.
:::

A worked example with illustrative numbers:

- 25 prompts × 3 engines × 2 runs = **150 answers** per month
- Your brand is mentioned in 21 of them: 21 ÷ 150 = **14% share of mentions**
- Your site is linked as a source in 9: 9 ÷ 150 = **6% share of citations**
- Your main competitor is mentioned in 42: 42 ÷ 150 = **28%**

That tells you more than any single answer. You're mentioned half as often as the competitor, and cited even less. Next, look at which prompts they win and which pages the engines cite for them. That's your content and PR list.

Pair this with business data. Google Search Console includes AI Overviews and AI Mode traffic in the normal "Web" search type ([Google Search Central](https://developers.google.com/search/docs/appearance/ai-features)), and referral traffic from ChatGPT and Perplexity shows up in your analytics as referrals. Add "How did you hear about us?" to your forms. Buyers will tell you when an AI assistant sent them.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## Where to start this month

:::checklist "AI search citation checklist"
- Check robots.txt and CDN bot rules for Googlebot, OAI-SearchBot and PerplexityBot
- View source on your top 10 pages and confirm the key answers are in the HTML
- Write your 25-prompt set from sales calls
- Run the first share-of-answers baseline across three engines
- Rewrite your three most important pages answer-first, with sourced numbers
- Fix entity facts on your site, LinkedIn and your top five directories
- Pitch one industry publication or partner for an accurate mention
- Re-run the prompt set in 30 days and compare
:::

:::faq
### How do I get my website cited in Google AI Overviews?
Make sure the page is indexed and eligible to show with a snippet, then make it the clearest, best-sourced answer to the question. Google says there are no extra requirements or special optimizations for AI Overviews beyond SEO fundamentals. Answer-first sections, accurate structured data that matches the page and strong rankings for the query all help.
### How do I get cited by ChatGPT?
Allow OAI-SearchBot in robots.txt so your pages can appear in ChatGPT search, keep key content server-rendered, and publish specific, sourced answers to the questions buyers ask. Mentions on sites ChatGPT already cites for your topic help too. Blocking GPTBot only affects training, not search.
### How do I get cited by Perplexity?
Allow PerplexityBot and Perplexity-User, which Perplexity uses to surface and fetch pages for answers. Then write passages that answer the question directly and cite original sources. Perplexity shows its sources prominently, so clear, checkable claims tend to be the passages it quotes.
### Does llms.txt help with AI search?
Not in any documented way. llms.txt is an unofficial proposal for a Markdown summary of your site. Google says you don't need new machine-readable files for its AI features, and no major engine documents using llms.txt for citations. It's harmless if it's accurate, but it isn't a ranking or citation lever.
### How long does it take to get cited in AI answers?
It depends on how often the engines recrawl your pages and how competitive the questions are. Fixes like unblocking a crawler can show up within weeks. Content and third-party mentions take longer. Measure monthly and look for a trend over a quarter rather than week-to-week changes.
### Can I pay to be cited in AI Overviews or ChatGPT?
Not in the organic answer itself. Citations come from what the engines retrieve and select. Ads may appear around AI features on some platforms, but they're labeled as ads and are separate from the cited sources.
:::

## The short version

Getting cited in AI search is mostly good SEO done with more discipline: let the right crawlers in, answer questions directly, prove what you say, describe your company the same way everywhere, and earn mentions elsewhere. Skip the shortcuts, measure share of answers every month, and fix what the numbers point to. If you want a baseline, start with a [growth audit](marketing:/growth-audit/) or our [AI search readiness checklist](marketing:/resources/ai-search-readiness-checklist/).
