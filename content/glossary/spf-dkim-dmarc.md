---
title: "SPF, DKIM and DMARC Explained (With Examples)"
slug: spf-dkim-dmarc
excerpt: "SPF, DKIM and DMARC are the three DNS records that prove your email is really from you. Here is what each does, example records, and a safe rollout to p=reject."
primary_kw: spf dkim dmarc
secondary_kws: [what is dmarc, dmarc policy, spf record, dkim record, email authentication]
intent: informational
audience: "B2B marketing leads and founders who send email"
short_definition: "SPF, DKIM and DMARC are email authentication standards. SPF lists servers allowed to send for your domain, DKIM signs messages cryptographically, and DMARC tells receivers what to do when checks fail and sends you reports."
taxonomies:
  glossary_category: [email-marketing]
  td_service_tag: [email-marketing]
related_terms: [bimi, email-warm-up, email-marketing, marketing-automation]
related_service: email-marketing
internal_links:
  - /services/email-marketing/
  - /glossary/bimi/
  - /glossary/email-warm-up/
  - /glossary/email-marketing/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "SPF, DKIM and DMARC explained with example records, the Gmail, Yahoo and Outlook sender rules, and a safe step-by-step rollout from p=none to p=reject."
---

SPF, DKIM and DMARC are three DNS-based standards that let mailbox providers check whether an email claiming to be from your domain really is. **SPF** lists the servers allowed to send for your domain. **DKIM** adds a cryptographic signature to each message. **DMARC** ties the two to the visible From address, tells receivers what to do when a message fails, and sends you reports. Together, SPF, DKIM and DMARC are now the minimum for reliable inbox placement.

## Why SPF, DKIM and DMARC matter

Since February 1, 2024, Gmail has required senders of 5,000 or more messages a day to personal Gmail accounts to set up SPF, DKIM and DMARC ([Google](https://support.google.com/a/answer/81126)). Yahoo began enforcing similar rules in February 2024 ([Yahoo Sender Hub](https://senders.yahooinc.com/best-practices/)), and Microsoft announced matching requirements for Outlook.com consumer domains in 2025 ([Microsoft](https://techcommunity.microsoft.com/blog/microsoftdefenderforoffice365blog/strengthening-email-ecosystem-outlook%e2%80%99s-new-requirements-for-high%e2%80%90volume-senders/4399730)).

:::stats "Gmail's bulk sender rules" highlight=1
5,000+ | messages a day to personal Gmail accounts makes you a bulk sender | Google, 2024 | https://support.google.com/a/answer/81126
0.30% | spam rate in Postmaster Tools that bulk senders must stay below (Google advises under 0.10%) | Google, 2024 | https://support.google.com/a/answer/81126
1024-bit | minimum DKIM key length for personal Gmail accounts; 2048-bit recommended | Google, 2024 | https://support.google.com/a/answer/81126
:::

Even below 5,000 a day, unauthenticated mail is more likely to land in spam, and your domain is easier to spoof in phishing attacks.

## How SPF, DKIM and DMARC work

| Standard | DNS record (example) | What it checks | Watch out for |
|---|---|---|---|
| SPF | `v=spf1 include:_spf.google.com include:sendgrid.net -all` on the root domain | The sending server's IP is listed | Max 10 DNS-querying lookups, or SPF returns permerror ([RFC 7208](https://datatracker.ietf.org/doc/html/rfc7208#section-4.6.4)) |
| DKIM | A public key at `selector._domainkey.example.com` | The signature matches, so the message wasn't altered | Each sending tool needs its own selector and key |
| DMARC | `v=DMARC1; p=none; rua=mailto:dmarc@example.com` at `_dmarc.example.com` | SPF or DKIM passes **and** aligns with the From domain | p=none only monitors; it doesn't protect |

**Alignment** is the part people miss. A newsletter tool can pass SPF for its own domain, but DMARC only passes if the passing domain matches your From domain. That's why each tool needs a custom DKIM domain or custom return-path.

**DMARC policies** ([RFC 9989](https://www.rfc-editor.org/rfc/rfc9989.html), which replaced RFC 7489 in May 2026):

- **p=none:** monitor only. Receivers deliver as normal and send you reports.
- **p=quarantine:** treat failing mail as suspicious, usually sending it to spam.
- **p=reject:** treat failing mail as invalid; receivers may refuse it.

RFC 9989 also removed the old pct tag and added a t (test mode) tag, though existing v=DMARC1 records keep working.

## Example: auditing a typical B2B domain

A hypothetical company sends from Google Workspace, HubSpot, Zendesk and an invoicing tool. Their SPF record has 4 includes, but HubSpot's and Zendesk's includes nest further lookups, bringing the total to 12. SPF fails with permerror for everyone.

The fix: remove the invoicing tool's include (it sends from its own domain), and authenticate HubSpot with DKIM on a subdomain instead of SPF on the root. Lookups drop to 8. DMARC aggregate reports then show all four tools passing aligned DKIM, so the team moves from p=none to p=quarantine, and six weeks later to p=reject.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to set up SPF, DKIM and DMARC

:::steps
1. List every sender | Workspace or Microsoft 365, CRM, newsletter, help desk, billing, website forms. Ask finance and support too.
2. Publish one SPF record | One TXT record per domain, includes for tools that need it, ending in -all or ~all, under 10 lookups.
3. Turn on DKIM everywhere | Generate keys in each tool (2048-bit where supported) and publish the CNAME or TXT records it gives you.
4. Start DMARC at p=none | Add rua reporting so you see who is sending as you.
5. Read the reports for 2–4 weeks | Fix every legitimate sender that fails alignment.
6. Tighten the policy | Move to p=quarantine, then p=reject once reports are clean. That also makes you eligible for [BIMI](/glossary/bimi/).
:::

:::myth-fact
Myth: p=none means you're protected by DMARC.
Fact: p=none only asks for reports. It meets Gmail's minimum, but spoofed mail is still delivered. Protection starts at p=quarantine or p=reject.
:::

Authentication gets you through the door. Reputation keeps you there, which is where [email warm-up](/glossary/email-warm-up/) and list hygiene come in.

:::faq
### Do I need all three: SPF, DKIM and DMARC?
Yes, if you send marketing or bulk email. Gmail, Yahoo and Outlook.com require all three for high-volume senders, and DMARC can't pass reliably without SPF or DKIM aligned to your From domain.
### What DMARC policy should I start with?
Start with p=none and a rua reporting address. Use the reports to find and fix every legitimate sender, then move to p=quarantine and p=reject. Jumping straight to reject can block your own invoices or support replies.
### Why does my SPF record fail with too many lookups?
SPF allows a maximum of 10 DNS-querying mechanisms, and nested includes count. Remove senders you no longer use, move some tools to DKIM-only on a subdomain, or use separate subdomains for marketing mail.
### Can I have two SPF records?
No. A domain must publish one SPF record. Two records cause a permanent error. Merge all includes into a single record.
:::

:::cta "Want your emails to reach the inbox?" "See email marketing" /services/email-marketing/
We audit your SPF, DKIM and DMARC setup and take you safely to p=reject.
:::
