---
title: "What is Email Warm-Up? A Safe Schedule for New Domains"
slug: email-warm-up
excerpt: "Email warm-up is gradually increasing send volume from a new domain or IP so mailbox providers learn to trust it. Here is how it works and a sample schedule."
primary_kw: email warm-up
secondary_kws: [email warmup, domain warm-up, ip warm-up, warm up email domain, email warm-up schedule]
intent: informational
audience: "B2B marketing and sales teams sending email"
short_definition: "Email warm-up is the process of gradually increasing sending volume from a new or dormant domain or IP address, starting with your most engaged recipients, so mailbox providers build a positive sender reputation before you send at full scale."
taxonomies:
  glossary_category: [email-marketing]
  td_service_tag: [email-marketing]
related_terms: [spf-dkim-dmarc, bimi, email-marketing, marketing-automation]
related_service: email-marketing
internal_links:
  - /services/email-marketing/
  - /glossary/spf-dkim-dmarc/
  - /glossary/bimi/
  - /glossary/marketing-automation/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Email warm-up explained: why new domains and IPs need it, what Google's sender guidelines say, a sample 4-week schedule with numbers, and when automated warm-up tools backfire."
---

Email warm-up is the practice of starting a new sending domain or IP address at low volume and increasing it step by step, beginning with the people most likely to open and reply. Mailbox providers like Gmail and Outlook judge senders on their history. A new domain has none, so a sudden blast of thousands of emails looks exactly like a spammer. Warm-up builds that history deliberately.

## Why email warm-up matters

Google's own sender guidelines describe warm-up in plain terms: "Start with a low sending volume to engaged users, and slowly increase the volume over time," and avoid sudden volume spikes if you don't have a history of sending large volumes ([Google Workspace Admin Help](https://support.google.com/a/answer/81126)).

:::stats "The thresholds warm-up protects" highlight=0
0.10% | spam complaint rate Google advises staying below in Postmaster Tools | Google, 2024 | https://support.google.com/a/answer/81126
0.30% | spam rate bulk senders must never reach | Google, 2024 | https://support.google.com/a/answer/81126
0.3% | Yahoo's spam rate ceiling for all senders | Yahoo Sender Hub, 2024 | https://senders.yahooinc.com/best-practices/
:::

A warm-up that sends to unengaged or old contacts can breach those thresholds in a single send. Recovering a damaged domain reputation takes far longer than building a good one.

## How email warm-up works

Providers watch a few signals closely while your volume grows:

| Signal | Good sign | Bad sign |
|---|---|---|
| Spam complaints | Well under 0.1% | Approaching 0.3% |
| Engagement | Opens, replies, moves to inbox | Deletes without opening, spam reports |
| Bounces | Low hard-bounce rate | Many unknown users (old or bought lists) |
| Consistency | Steady daily volume | Spikes and long silences |
| Authentication | SPF, DKIM, DMARC aligned | Failures or missing records |

You need [SPF, DKIM and DMARC](/glossary/spf-dkim-dmarc/) in place before day one. Warm-up builds reputation; authentication proves identity. You need both.

**Domain vs IP warm-up.** Most B2B teams on shared email platforms only warm the domain, because the platform manages IP reputation. If you move to a dedicated IP, that IP needs warming too.

## Example: a 4-week warm-up schedule

A hypothetical SaaS company moves its newsletter to a new subdomain, news.example.com, with 20,000 subscribers. It sorts them by engagement: 4,000 opened in the last 30 days, 6,000 in the last 90 days, the rest older.

| Week | Daily volume | Who gets it |
|---|---|---|
| 1 | 500 → 1,000 | 30-day openers only |
| 2 | 1,500 → 3,000 | 30-day openers, then 90-day openers |
| 3 | 4,000 → 6,000 | All 90-day engaged contacts |
| 4 | 8,000 → full list | Add older contacts in small batches, with a re-engagement email first |

The rule: only step up if complaints stay under 0.1% and bounces stay low in the previous step. If a metric spikes, hold or step back for a few days. These numbers are a starting point, not a standard. Your platform may publish its own guidance.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to warm up an email domain

:::steps
1. Authenticate first | SPF, DKIM and DMARC aligned for the new domain or subdomain. Set up Google Postmaster Tools.
2. Clean the list | Remove hard bounces, role addresses you never opted in and anyone inactive for 12+ months.
3. Segment by engagement | Start with recent openers, clickers and customers.
4. Send your best content | Early sends should be the emails people want most: product updates, useful guides.
5. Increase gradually | Raise daily volume in steps and keep the send rate steady rather than in bursts.
6. Watch the dashboards | Postmaster Tools spam rate and domain reputation, bounce and complaint rates in your platform.
7. Keep it warm | Send consistently once warm. Long gaps can mean you need to warm up again.
:::

:::myth-fact
Myth: Automated warm-up tools that trade fake opens and replies between inboxes will fix your reputation.
Fact: Google tells senders to build volume with engaged real recipients. Artificial engagement doesn't change how your actual subscribers react, and it can hide problems until a real send fails.
:::

Once your reputation is stable, automate the rest with [marketing automation](/glossary/marketing-automation/) and consider [BIMI](/glossary/bimi/) for brand recognition.

:::faq
### How long does email warm-up take?
For most B2B domains, two to six weeks, depending on list size, engagement and your target daily volume. Larger lists and dedicated IPs take longer. Let your metrics, not the calendar, decide when to step up.
### Do I need to warm up a new domain for cold email?
Yes, and more carefully. Cold email has no prior opt-in, so complaints come faster. Use a separate domain or subdomain, keep daily volumes low per inbox, and follow privacy and anti-spam laws in each market you send to.
### Should I use a subdomain for marketing email?
Often, yes. A subdomain such as news.example.com separates marketing reputation from your main domain, so a bad campaign doesn't hurt sales or support email. It still needs its own authentication and warm-up.
### What if my emails already go to spam?
Pause sends to unengaged contacts, check authentication, look at Postmaster Tools, and restart with only your most engaged segment at lower volume. Treat it like a warm-up from scratch.
:::

:::cta "Moving platforms or launching a new domain?" "See email marketing" /services/email-marketing/
We plan the warm-up, clean the list and watch the metrics so your first big send lands.
:::
