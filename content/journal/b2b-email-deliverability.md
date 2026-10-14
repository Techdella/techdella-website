---
title: "B2B Email Deliverability: Gmail, Yahoo and Outlook Rules"
slug: b2b-email-deliverability
excerpt: "What B2B senders must do to reach the inbox: SPF, DKIM and DMARC, one-click unsubscribe, spam complaints under 0.3%, and the Outlook requirements Microsoft enforced in 2025. With a side-by-side table and a setup checklist."
primary_kw: email deliverability
secondary_kws: [b2b email deliverability, gmail bulk sender requirements, yahoo sender requirements, outlook high-volume sender requirements, spf dkim dmarc, one-click unsubscribe, spam rate]
intent: informational
audience: "B2B marketers, founders and RevOps leads who send newsletters, nurture sequences or outbound email"
angle: "Puts the official Gmail, Yahoo and Microsoft rules side by side from the providers' own pages, explains what changes for B2B (Workspace and Microsoft 365 recipients), and turns the spam-rate limit into plain numbers."
proof: "Google, Yahoo and Microsoft sender guidelines; Validity 2025 Email Deliverability Benchmark."
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
categories: [email]
cta_service: email-marketing
taxonomies:
  td_service_tag: [email-marketing]
tldr: "Email deliverability is whether your messages reach the inbox rather than spam or nowhere. For B2B senders that now means SPF, DKIM and an aligned DMARC record, one-click unsubscribe on marketing email, a user-reported spam rate kept below 0.3% (ideally under 0.1%), and lists of people who actually asked to hear from you."
rank_math_description: "B2B email deliverability in {{year}}: Gmail, Yahoo and Outlook sender rules side by side, SPF, DKIM, DMARC, one-click unsubscribe and the 0.3% spam limit."
internal_links:
  - /services/email-marketing/
  - /resources/email-deliverability-checklist/
  - /glossary/email-marketing/
  - /glossary/marketing-automation/
  - /glossary/a-b-testing/
  - /b2b-lead-generation/
  - /b2b-marketing-automation/
---

Email deliverability is whether your email reaches the inbox, rather than the spam folder or nowhere at all. For B2B senders it comes down to four things: authenticate your domain with SPF, DKIM and DMARC; make unsubscribing one click; keep spam complaints well under 0.3%; and only email people who asked to hear from you. Gmail and Yahoo have enforced these as rules for bulk senders since February 2024, and Microsoft began enforcing its version for Outlook.com in May 2025.

If you skip them, it doesn't matter how good the subject line is. Nobody sees it.

:::takeaways
- Authenticate every sending domain with SPF, DKIM and DMARC, and make sure the From domain aligns.
- Add one-click unsubscribe headers to marketing email and honor requests within 48 hours.
- Watch your user-reported spam rate in Google Postmaster Tools. Stay below 0.1%; never reach 0.3%.
- Microsoft rejects non-compliant bulk mail to Outlook.com, Hotmail and Live addresses.
- Business inboxes on Google Workspace and Microsoft 365 apply their own filtering, so the same habits matter there too.
:::

## How much email misses the inbox

More than you'd think. Validity's 2025 benchmark, based on 2024 data, found that one in six legitimate marketing emails didn't reach the inbox.

:::stats "Where marketing email landed in 2024" highlight=0
83.5% | global inbox placement rate | Validity Email Deliverability Benchmark, 2025 | https://validity.com/wp-content/uploads/2025/03/2025-Benchmark-Report-FINAL.pdf
6.7% | of email went to spam | Validity Email Deliverability Benchmark, 2025 | https://validity.com/wp-content/uploads/2025/03/2025-Benchmark-Report-FINAL.pdf
9.8% | went missing: not delivered to inbox or spam | Validity Email Deliverability Benchmark, 2025 | https://validity.com/wp-content/uploads/2025/03/2025-Benchmark-Report-FINAL.pdf
:::

Results vary a lot by mailbox provider. Microsoft was the hardest to reach:

:::chart "Inbox placement rate by mailbox provider, 2024" unit=% max=100 highlight=1
Gmail | 87.2
Microsoft | 75.6
Yahoo/AOL | 86.0
Apple | 76.3
source: Validity Email Deliverability Benchmark, 2025 | https://validity.com/wp-content/uploads/2025/03/2025-Benchmark-Report-FINAL.pdf
:::

For B2B senders this matters twice. Many of your recipients use Microsoft 365 or Google Workspace at work, and your nurture sequences, webinar invites and newsletters all run through the same sending reputation. One bad campaign can hurt the rest.

## Email deliverability requirements: Gmail, Yahoo and Outlook compared

Here are the rules from each provider's own guidance, side by side.

| | Gmail | Yahoo | Outlook (Microsoft consumer) |
|---|---|---|---|
| Who it applies to | Senders of 5,000+ messages a day to personal Gmail accounts; status is permanent once reached | Bulk senders to Yahoo addresses | Senders of 5,000+ messages to Outlook.com, Hotmail and Live addresses from the same From domain |
| Authentication | SPF and DKIM | SPF and DKIM | SPF and DKIM must pass |
| DMARC | Required; policy can be p=none | Required; at least p=none, and DMARC must pass | Required; at least p=none, and DMARC must pass |
| Alignment | From domain aligns with SPF or DKIM domain | Relaxed alignment accepted | SPF or DKIM (at least one) aligns with From domain |
| Spam rate | Below 0.3% in Postmaster Tools; target below 0.1% | Below 0.3% | No published threshold |
| Unsubscribe | One-click (List-Unsubscribe-Post header) plus a visible link | One-click list-unsubscribe header; honor within 2 days | Not a stated requirement; still best practice |
| Other | TLS, valid forward and reverse DNS (PTR), RFC 5322 format | Valid forward and reverse DNS, RFC 5321 and 5322 compliance | Non-compliant mail rejected with error 550 5.7.515 |
| In force since | February 2024 | February 2024 | May 5, 2025 |
| Official source | [Google sender guidelines](https://support.google.com/a/answer/81126) | [Yahoo sender best practices](https://senders.yahooinc.com/best-practices/) | [Microsoft: 550 5.7.515](https://support.microsoft.com/en-us/outlook/fix-ndr-error-550-5-7-515-in-outlook-com) and [Microsoft's announcement](https://techcommunity.microsoft.com/blog/microsoftdefenderforoffice365blog/strengthening-email-ecosystem-outlook%E2%80%99s-new-requirements-for-high%E2%80%90volume-senders/4399730) |

A few details people miss:

- **Gmail counts by primary domain.** Google counts all messages from the same primary domain toward the 5,000 threshold, and once you reach it, you're treated as a bulk sender permanently ([Google FAQ](https://support.google.com/a/answer/14229414)).
- **Gmail's rules cover personal accounts.** Google says its sender requirements and enforcement apply to mail sent to personal Gmail accounts. Mail to companies on Google Workspace is still filtered, with whatever extra rules each company's admin has set. Meet the rules anyway.
- **Unsubscribes have a deadline.** Google recommends processing them within 48 hours. Yahoo requires 2 days.
- **Microsoft moved from junk to rejection.** Microsoft first said non-compliant mail would go to junk, then confirmed it would be rejected with "550; 5.7.515 Access denied."

## What SPF, DKIM and DMARC actually do

These three DNS records prove that mail claiming to be from your domain really is.

- **SPF** lists the servers allowed to send mail for your domain. Every tool that sends as you, including your CRM, your email platform and your help desk, needs to be covered.
- **DKIM** adds a cryptographic signature to each message, which receivers check against a public key in your DNS. Google requires keys of at least 1024 bits for personal Gmail accounts and recommends 2048 where your provider supports it ([Google](https://support.google.com/a/answer/81126)).
- **DMARC** tells receivers what to do when a message fails SPF and DKIM alignment, and where to send reports. Start at `p=none` to collect reports, fix every legitimate sender, then move toward `p=quarantine` or `p=reject`.

**Alignment** is the part that catches B2B teams. If your email platform signs with its own domain and you never set up custom DKIM, the message can pass DKIM and still fail DMARC, because the signing domain doesn't match your From address.

## Keeping spam complaints under 0.3%

Google measures the user-reported spam rate: the share of your mail delivered to Gmail inboxes that users mark as spam. Here's what the limits mean in plain numbers:

- Send a campaign that lands in **20,000** Gmail inboxes.
- At **0.3%**, that's 20,000 × 0.003 = **60 spam reports**. That's the line you must stay under.
- At **0.1%**, it's 20,000 × 0.001 = **20 reports**. That's Google's recommended target.

Twenty people out of 20,000 is not many. One campaign to a cold or purchased list can blow through it. Google also says bulk senders above 0.3% become ineligible for mitigation until the rate stays below 0.3% for seven consecutive days ([Google FAQ](https://support.google.com/a/answer/14229414)).

What keeps complaints low:

- **Permission.** Email people who opted in or who have a clear, recent business relationship with you.
- **Expectations.** Tell people at signup what you'll send and how often. Then do that.
- **Easy exit.** A visible unsubscribe link and one-click headers. People who can't find the unsubscribe link click "Report spam."
- **Recency.** Stop mailing contacts who haven't opened or clicked in six months. Try one re-permission email first.
- **Relevance.** Segment by role, industry and stage. Our [B2B marketing automation guide](/b2b-marketing-automation/) covers how.

:::cta "Is your email reaching the inbox?" "See our email marketing service" marketing:/services/email-marketing/
We check your authentication and alignment, fix your sending setup, clean your lists and build nurture programs that people actually want to receive.
:::

## Moving DMARC from monitoring to enforcement

A `p=none` policy meets the Gmail, Yahoo and Outlook minimum, but it doesn't stop anyone spoofing your domain. Moving to enforcement protects your brand and your reputation. Do it in stages so you don't block your own mail:

:::steps "From p=none to p=reject without breaking anything"
1. Collect reports for a month | Publish DMARC at p=none with an rua reporting address and use a report reader to see every source sending as your domain.
2. Fix or remove each source | For every legitimate tool, add it to SPF or set up custom DKIM so it aligns. Retire tools nobody owns.
3. Quarantine a slice | Move to p=quarantine with pct=10, then 25, 50 and 100 over a few weeks, watching reports and bounces at each step.
4. Reject | When reports show only aligned, legitimate mail, move to p=reject. Keep reading reports monthly, because new tools get added.
:::

## New domain or new platform? Warm it up

Mailbox providers judge new sending domains and IPs with caution. If you launch a new domain, move to a new email platform or start sending from a new subdomain, build volume gradually:

- Start with your most engaged contacts: people who opened or clicked in the last 30 days.
- Send small volumes first and increase over several weeks, watching bounces, complaints and Postmaster Tools at each step.
- Keep content and frequency consistent with what those contacts already expect.
- Don't import an old, unengaged list on day one. That's the fastest way to start with a bad reputation.

## Where B2B deliverability differs

Consumer email advice doesn't map neatly onto B2B. Watch for these:

- **Corporate gateways.** Many companies route mail through security gateways that rewrite links, scan attachments and hold suspicious mail. Keep links on your own domain, avoid URL shorteners and don't attach files to marketing email.
- **Small, high-value lists.** A 3,000-contact B2B list is normal. Each complaint weighs more than it does on a million-contact consumer list.
- **Role addresses.** Addresses like info@ or sales@ often reach shared inboxes where someone who never signed up hits "spam." Don't import them into marketing sends.
- **Outbound and marketing on one domain.** Cold outreach can damage the reputation your newsletter depends on. If you do outbound, send from a separate, authenticated subdomain at low volume, and follow the same permission and opt-out rules.
- **Bounces from data decay.** People change jobs often. Remove hard bounces immediately and verify lists that haven't been mailed in a while.

## Common deliverability problems and their fixes

| Symptom | Likely cause | Fix |
|---|---|---|
| Sudden drop in opens at one provider | Reputation dip or authentication change at that provider | Check Postmaster Tools or provider bounces; review recent DNS or tool changes |
| Bounces with 550 5.7.515 | Outlook rejecting mail that fails authentication | Make SPF and DKIM pass, publish DMARC, align at least one with the From domain |
| DMARC failures in reports | A tool sending as your domain without custom DKIM or SPF include | Add each sender to SPF and set up custom DKIM signing |
| Spam rate climbing | Old or unengaged contacts, unclear expectations, hidden unsubscribe | Sunset inactive contacts, re-confirm permission, make unsubscribe obvious |
| Good inbox rate, poor replies | Content or targeting, not deliverability | Segment better and test with [A/B tests](marketing:/glossary/a-b-testing/) |

:::myth-fact
Myth: The bulk sender rules only matter if you send millions of emails.
Fact: Gmail's threshold is 5,000 messages a day from one primary domain, and status is permanent once reached. Microsoft's is 5,000 to its consumer services. A busy B2B newsletter day can cross it.
Myth: Once SPF and DKIM pass, you're done.
Fact: DMARC also checks alignment. Mail can pass DKIM signed by your platform's domain and still fail DMARC for yours.
Myth: Unsubscribe links hurt your list size, so hide them.
Fact: Hidden unsubscribe links produce spam complaints, which damage deliverability for everyone on your list. Gmail and Yahoo require one-click unsubscribe on marketing email.
Myth: Deliverability is a technical problem only.
Fact: Authentication gets you through the door. Permission, relevance and list hygiene keep you there.
:::

:::quote "Joanna Okedara-Kalu" "Founder & CEO, TechDella"
When email performance drops, authentication is the first thing I check, but it's rarely the whole story. Often a list was imported without fresh permission, or a new tool started sending as the domain and nobody added it to SPF or DKIM. Write down every system that sends email as your domain. Most deliverability fixes start with that list.
:::

## B2B email deliverability checklist

:::checklist "Set up once, then check monthly"
- List every tool that sends email as your domain (CRM, email platform, help desk, billing)
- Publish one SPF record that covers all of them, within the 10 DNS lookup limit
- Set up custom DKIM signing for each tool, 2048-bit where supported
- Publish DMARC at p=none with a reporting address; read the reports
- Confirm the From domain aligns with SPF or DKIM on every message type
- Add List-Unsubscribe and List-Unsubscribe-Post headers to marketing email
- Process unsubscribes within 48 hours
- Register for Google Postmaster Tools and check the spam rate weekly
- Keep TLS on and confirm forward and reverse DNS for sending IPs
- Separate outbound from marketing sending with an authenticated subdomain
- Remove hard bounces immediately; sunset contacts inactive for six months
- Move DMARC toward quarantine or reject once reports are clean
:::

Want a printable version? Download our [email deliverability checklist](marketing:/resources/email-deliverability-checklist/).

:::faq
### What is email deliverability?
Email deliverability is the ability of your emails to reach recipients' inboxes rather than spam folders or being blocked. It depends on authentication (SPF, DKIM, DMARC), your sending reputation, complaint and bounce rates, and whether recipients want your email. Delivery means the server accepted the message; deliverability means it reached the inbox.
### What are Gmail's bulk sender requirements?
Senders of more than 5,000 messages a day to personal Gmail accounts must set up SPF, DKIM and DMARC, align the From domain with SPF or DKIM, support one-click unsubscribe on marketing email, use TLS and valid DNS, and keep their user-reported spam rate below 0.3%. Google recommends staying under 0.1%.
### What are Microsoft Outlook's 2025 sender requirements?
Since May 5, 2025, domains that send 5,000 or more messages to Outlook.com, Hotmail and Live addresses must pass SPF and DKIM, publish a DMARC record of at least p=none that passes, and align SPF or DKIM with the From domain. Microsoft rejects non-compliant mail with the error 550 5.7.515.
### What is a good spam complaint rate?
Below 0.1% of delivered mail, as measured in Google Postmaster Tools. Google and Yahoo both set 0.3% as the line bulk senders must stay under. On a send that reaches 20,000 Gmail inboxes, 0.1% is 20 reports and 0.3% is 60.
### Do these rules apply to B2B email sent to company domains?
Google's requirements and enforcement apply to personal Gmail accounts, and Microsoft's to its consumer services. Business inboxes on Google Workspace and Microsoft 365 use their own filtering, and many companies add security gateways on top. Meeting the same standards is the safest way to reach all of them.
### How do I check if my emails are going to spam?
Watch for drops in opens or clicks by mailbox provider, read your bounce messages, check Google Postmaster Tools for spam rate and authentication, and review DMARC reports for failures. Seed testing with a small set of addresses across providers also helps you spot spam-folder placement early.
:::

## The short version

Deliverability isn't a trick. Authenticate every sender, make unsubscribing easy, email people who asked for it, and watch your spam rate like a revenue number, because it is one. Once the basics are in place, the work moves to relevance: the right message to the right person at the right stage. Our guide to [B2B lead generation](/b2b-lead-generation/) shows how email fits into the wider pipeline.
