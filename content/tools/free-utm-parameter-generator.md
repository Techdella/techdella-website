---
title: "Free UTM Parameter Generator"
slug: free-utm-parameter-generator
excerpt: "A free UTM parameter generator that builds clean, lowercase campaign URLs for GA4, flags naming mistakes, and keeps the query strings your page already uses."
rank_math_description: "Free UTM parameter generator: build clean GA4 campaign URLs in seconds. Lowercase values, no spaces, and warnings for the mistakes that split your reports."
tool_component: utm-parameter-generator
primary_kw: utm parameter generator
secondary_kws: [utm builder, campaign url builder, utm link generator, ga4 utm parameters, utm tracking]
intent: transactional
audience: "Marketers, founders and agencies who share links in ads, emails and social posts and report in GA4"
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
gate: email
ad_slots: true
how_to:
  - "Paste the landing page URL | Use the exact page people should land on. If you leave off https://, the tool adds it. Query strings already on the URL, such as ?ref=footer, are kept."
  - "Fill in source, medium and campaign | These three are required. Source is where the click comes from (linkedin), medium is the channel type (paid-social, email, cpc), campaign is the push (q4-growth-audit)."
  - "Add term and content if you need them | Both are optional. Use utm_content to tell two ads, buttons or links in the same campaign apart. Use utm_term for a paid keyword."
  - "Keep Tidy values switched on | It lowercases every value, turns spaces into hyphens and collapses double hyphens. Switch it off only if you must match an existing naming scheme, and the tool will warn you about spaces and capitals."
  - "Build the URL and enter your email once | The first time you run the tool, it asks for your email to show results. After that, this browser shows results straight away."
  - "Copy the URL or just the parameters | Copy URL gives you the full tagged link. Copy parameters only gives you the part after the question mark, for ad platforms that take a tracking template or URL suffix."
faq:
  - "What is a UTM parameter generator? | A UTM parameter generator adds tracking tags to the end of a link, so your analytics tool can tell where each visit came from. You enter the page URL plus a source, medium and campaign, and it returns one URL you can paste into an ad, email or post. Ours also tidies the values so your GA4 reports don't split one channel into several rows."
  - "Which UTM parameters are required in GA4? | GA4 asks you to always use utm_source, utm_medium and utm_campaign. Missing values show as (not set) in reports. Google also recommends utm_id and utm_source_platform. This tool builds the three core tags plus utm_term and utm_content. If your team uses utm_id, add it by hand to the copied link."
  - "Are UTM parameters case sensitive? | Yes. Google's Analytics Help says utm_source=google and utm_source=Google are different values, and recommends lowercase as a standard. That's why the Tidy values option lowercases everything by default. If you turn it off, the tool flags any value with capital letters."
  - "What medium should I use for LinkedIn or Meta ads? | Use a medium that starts with paid, such as paid-social, or cpc. GA4's default channel group puts manual traffic in Paid Social when the source is a known social site and the medium matches cp, ppc, retargeting or anything starting with paid. With a medium like sponsored, a LinkedIn click lands in Organic Social, so your paid results look like unpaid posts."
  - "Do I need UTMs on Google Ads links? | Usually not. Google Ads auto-tagging adds a gclid to each click, and Google says that in GA4 auto-tagging takes priority over manual tags. The tool shows a note when you enter google or bing with medium cpc. Use manual UTMs only if auto-tagging is off or you have a specific reason."
  - "Should I add UTMs to links between my own pages? | No. UTMs belong on links that bring people to your site from somewhere else. Tagging an internal banner or menu link relabels a visitor who already arrived from search, an ad or an email, so your reports credit your own page instead. Track internal clicks with GA4 events instead."
  - "Why does the tool ask for my email? | The generator is free. We ask for an email once so we can send occasional practical guides on tracking and growth. After you enter it, results show right away on this browser. The link you build is created in your browser; only a one-line summary of the result is sent with your email."
---

A UTM parameter generator turns a plain link into a tracked campaign URL. You paste the page, add a source, medium and campaign, and get one link that tells GA4 exactly which ad, email or post sent each visitor. This one is free, runs in your browser, asks for your email once to show results, and cleans up your naming so reports stay readable.

It's built for marketers, founders and agencies who share links across several channels and then try to work out which ones produced leads.

:::takeaways
- Always fill **utm_source**, **utm_medium** and **utm_campaign**. GA4 shows missing values as "(not set)".
- UTM values are case sensitive. `LinkedIn` and `linkedin` become two rows in your report, so keep everything lowercase.
- Pick mediums GA4 recognizes (`cpc`, `paid-social`, `email`, `social`) so traffic lands in the right default channel.
- Don't tag Google Ads links if auto-tagging is on, and never tag links between your own pages.
- Keep a shared sheet of every link you build, so the whole team reuses the same names.
:::

## What the UTM parameter generator builds

UTM parameters are tags added after a `?` in a URL. GA4 reads them when someone lands on your page and stores them as the session's source, medium and campaign. Google's [Analytics Help page on campaign URLs](https://support.google.com/analytics/answer/10917952) lists nine parameters. This tool builds the five that almost every team uses:

| Parameter | Required here | What it answers | Example |
|---|---|---|---|
| `utm_source` | Yes | Which site, platform or list sent the click? | `linkedin`, `newsletter`, `google` |
| `utm_medium` | Yes | What type of channel was it? | `paid-social`, `email`, `cpc`, `social` |
| `utm_campaign` | Yes | Which promotion or push? | `q4-growth-audit` |
| `utm_term` | No | Which paid keyword? | `b2b-seo-agency` |
| `utm_content` | No | Which ad, button or link inside the campaign? | `carousel-v1` |

The other four (`utm_id`, `utm_source_platform`, `utm_creative_format` and `utm_marketing_tactic`) are for teams that upload cost data or use buying platforms. Google notes that the last two aren't currently reported in Analytics. If you need `utm_id`, add it to the copied link by hand.

:::stats "What GA4 does with your tags"
9 | campaign URL parameters GA4 documents, from utm_source to utm_marketing_tactic | Google Analytics Help, 2026 | https://support.google.com/analytics/answer/10917952
19 | default channels GA4 can sort your traffic into, from Paid Social to AI Assistant | Google Analytics Help, 2026 | https://support.google.com/analytics/answer/9756891
:::

## How it works

Everything happens in your browser. When you press **Build my URL**, the tool:

1. **Checks the address.** It accepts `example.com/page/` or a full `https://` URL. If you leave off the protocol, it adds `https://`. It rejects anything that isn't a web address with a proper domain.
2. **Tidies your values** (when the box is ticked, which is the default). It trims spaces at the ends, lowercases the text, turns any run of spaces into a hyphen and collapses double hyphens. So `Q4 Growth Audit` becomes `q4-growth-audit`.
3. **Keeps your existing query string.** A `?ref=footer` or `?plan=pro` already on the URL stays where it is. If the URL already has a UTM tag, the new value replaces it and you get a note saying so.
4. **Skips empty fields.** If you leave term or content blank, that tag doesn't appear in the link at all.
5. **Flags common mistakes.** With tidying off, it warns about spaces and capital letters in any value. It also warns when the page uses `http://`, and adds a note when you enter `google` or `bing` with medium `cpc`, because ad-platform auto-tagging may already cover those clicks.

You then get the full URL, a table of the parameters it set, and two copy buttons: **Copy URL** and **Copy parameters only**.

## How to read your results

The tool doesn't score your link. It gives you the link plus notes. Here's what each note means and what to do:

| What you see | What it means | What to do |
|---|---|---|
| No notes | The values are clean and the page uses https | Copy the link and log it in your tracking sheet |
| "contains spaces" (Improve) | A value has spaces, which show as `%20` or `+` in reports | Turn on Tidy values, or use hyphens |
| "has capital letters" (Improve) | GA4 will treat this as a separate value from the lowercase version | Lowercase it, unless you're matching an existing convention exactly |
| "The page uses http://" (Improve) | The redirect to https can lose referral information | Use the https version of the page |
| "Replaced an existing utm_…" (Note) | The pasted URL already had that tag | Check you meant to overwrite it |
| "Auto-tagging may already cover this" (Note) | You tagged a Google or Microsoft Ads search click by hand | Check whether auto-tagging is on before using the link |

## A worked example

Say you're promoting a growth audit with a LinkedIn carousel in Q4, and the link sits in your site footer too, so the URL already carries `?ref=footer`. You type the values the way people naturally do:

- **URL:** `techdella.com/growth-audit/?ref=footer`
- **Source:** `LinkedIn`
- **Medium:** `Paid Social`
- **Campaign:** `Q4 Growth Audit`
- **Content:** `carousel v1`

With Tidy values on, the tool returns:

`https://techdella.com/growth-audit/?ref=footer&utm_source=linkedin&utm_medium=paid-social&utm_campaign=q4-growth-audit&utm_content=carousel-v1`

Four things happened. It added `https://`. It kept `ref=footer`. It lowercased the values and swapped spaces for hyphens. And it left out `utm_term`, because you didn't fill it in.

Now check where this lands in GA4. Google's [default channel group rules](https://support.google.com/analytics/answer/9756891) put manual traffic in **Paid Social** when the source matches a list of social sites and the medium matches `cp`, `ppc`, `retargeting` or anything starting with `paid`. `linkedin` is a social source and `paid-social` starts with `paid`, so this traffic lands in Paid Social.

Change one value and the picture changes. With `utm_medium=sponsored`, the medium matches none of the paid patterns, so the visit can't be Paid Social. Because `linkedin` is still a social source, GA4 files it under **Organic Social** instead, and the carousel's leads get credited to your unpaid posts. That single word is the difference between a clean report and an argument about which channel worked.

When you test a second creative, change only `utm_content` to `carousel-v2`. In GA4, add **Session manual ad content** as a secondary dimension and compare the two.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## UTM best practices for clean GA4 reports

Google's own guidance boils down to one idea: pick a naming scheme and never break it. These are the rules we'd set before anyone builds a link.

:::checklist "Before you share a tagged link"
- Lowercase every value. Google's help page says Meta and meta are treated as different values.
- Use one source name per platform (always `linkedin`, never `linkedin.com` one week and `li` the next).
- Use one medium per channel type: `cpc` or `paid-social` for paid, `email` for email, `social` for organic posts.
- Put the date or quarter in the campaign name, not in the source or medium.
- Use `utm_content` to separate ads, buttons or links inside one campaign.
- Test the link: open it, check the page loads, and check the tags survive any redirect.
- Log every link in a shared sheet with the date, owner and where it was posted.
:::

A few channel-specific notes:

- **Email.** GA4 puts traffic in the Email channel when the source or medium is `email`, `e-mail`, `e_mail` or `e mail`. A medium of `newsletter` doesn't qualify, so use `utm_source=newsletter&utm_medium=email`. If your email platform adds its own tags, check they follow the same scheme.
- **Organic social.** GA4 counts a visit as Organic Social when the source is a known social site, or the medium is one of `social`, `social-network`, `social-media`, `sm`, `social network` or `social media`. Use `social` for unpaid posts and keep `paid-social` for ads.
- **Google Ads.** Google's [auto-tagging help page](https://support.google.com/google-ads/answer/3095550) says auto-tagging takes priority over manual tags in GA4. If it's on, you usually don't need UTMs on those ads at all.
- **Ad platforms with URL parameters.** Meta, LinkedIn and Google Ads let you add tracking parameters at the campaign or account level. Use **Copy parameters only** to paste the part after the `?` into those fields.

If your tracking also feeds a CRM, the same discipline pays off there. Clean source and campaign values make [marketing attribution](/glossary/marketing-attribution/) far easier to trust, whichever [attribution model](/glossary/attribution-modelling/) you use.

:::cta "Not sure which channel is working?" "See our paid ads service" /services/paid-ads/
We set up tracking, naming and reporting before we spend a dollar, so you can see which campaigns produce pipeline.
:::

## Common UTM mistakes

:::myth-fact
Myth: More UTM tags give you better data.
Fact: Consistent tags give you better data. Five tags with three spellings of the same source split one channel into three rows.
Myth: You should tag every link, including links between your own pages.
Fact: Tag only links that bring people in from outside. An internal tag relabels a visitor who arrived from search or email, so your own banner gets the credit.
Myth: Google Ads needs UTMs to show up in GA4.
Fact: Auto-tagging handles it. Google says auto-tagging takes priority over manual tags in GA4, so manual tags on those clicks are usually redundant.
:::

Three more mistakes we see often:

- **Putting UTMs on a URL that redirects.** Some redirects drop the query string. Always link to the final URL.
- **Using personal shorthand.** `fb`, `facebook` and `meta` are three sources to GA4. Agree on one name per platform.
- **Changing names mid-campaign.** If you rename `q4-launch` to `q4-launch-v2` halfway through, your report shows two half-campaigns. Use `utm_content` for variants instead.

## Where to go next

If you run paid campaigns on LinkedIn, Meta or Google, our [B2B paid ads guide](journal:/b2b-paid-ads-guide/) covers how we compare those channels once the tracking is clean. If you want help with the setup itself, our [paid ads team](/services/paid-ads/) builds tracking and naming into every account, and our [marketing automation](/services/marketing-automation/) work carries those UTM values into your CRM so sales can see where each lead came from.
