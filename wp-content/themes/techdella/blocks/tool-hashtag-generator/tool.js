// Hashtag Generator (§12.1). Suggestions come from the topic itself plus a
// curated, hand-picked list per theme (no API, runs in the browser).
import { tool, el, section, copyButton } from '../tool-kit/kit.js';

// theme: [trigger words, hashtags]
const LIST = {
  marketing: ['marketing market growth brand campaign', 'Marketing DigitalMarketing MarketingStrategy GrowthMarketing B2BMarketing MarketingTips ContentMarketing'],
  seo: ['seo search google rank ranking keyword serp', 'SEO SearchEngineOptimization SEOTips TechnicalSEO ContentStrategy GoogleSearch OrganicTraffic'],
  ai: ['ai artificial gpt chatgpt llm machine automation agent', 'AI ArtificialIntelligence GenerativeAI AIMarketing AITools MachineLearning Automation'],
  aisearch: ['aeo geo overviews answer perplexity', 'AISearch AEO GEO AIOverviews SearchMarketing'],
  ads: ['ads ad ppc paid advertising meta facebook tiktok linkedin google', 'PaidAds PPC PaidSocial PerformanceMarketing DigitalAdvertising AdCreative'],
  email: ['email newsletter inbox nurture', 'EmailMarketing Newsletter EmailStrategy MarketingAutomation Deliverability'],
  social: ['social instagram linkedin tiktok content creator video', 'SocialMediaMarketing ContentCreator SocialMediaTips ContentStrategy VideoMarketing'],
  leads: ['lead leads pipeline sales prospect demand', 'LeadGeneration DemandGen B2BSales SalesPipeline RevOps'],
  startup: ['startup startups founder founders mvp launch venture', 'Startup StartupLife Founders Entrepreneurship StartupGrowth BuildInPublic ProductLaunch'],
  saas: ['saas software b2b product app platform', 'SaaS B2BSaaS ProductLed SaaSMarketing SoftwareDevelopment ProductManagement'],
  fintech: ['fintech payment payments bank banking finance money lending', 'Fintech Payments DigitalBanking FinancialInclusion FinanceTech'],
  ecommerce: ['ecommerce shop store dtc retail shopify online', 'Ecommerce OnlineStore DTC ShopSmall RetailMarketing EcommerceTips'],
  design: ['design ux ui brand branding logo creative', 'Design UXDesign UIDesign Branding BrandIdentity CreativeDirection'],
  tech: ['tech developer code coding engineering web', 'Tech WebDevelopment SoftwareEngineering TechCommunity NoCode'],
  africa: ['africa nigeria lagos kenya ghana african', 'AfricaTech NigeriaTech LagosStartups AfricanStartups MadeInAfrica'],
  business: ['business smallbusiness company entrepreneur owner', 'SmallBusiness BusinessGrowth Entrepreneur BusinessTips Leadership'],
  health: ['health wellness fitness medical care', 'HealthTech Wellness HealthyLiving Fitness DigitalHealth'],
  food: ['food restaurant recipe cooking chef', 'Foodie FoodLovers RecipeIdeas HomeCooking RestaurantMarketing'],
  travel: ['travel trip tourism hotel', 'Travel TravelTips Wanderlust TravelGram Tourism'],
  fashion: ['fashion style clothing beauty skincare', 'Fashion StyleInspo Beauty Skincare OOTD'],
  education: ['education learning course school teach', 'Education EdTech LearningEveryDay OnlineLearning Teaching'],
  gaming: ['game gaming games esports', 'Gaming GameDev IndieGame Esports Gamers'],
  realestate: ['realestate property home housing', 'RealEstate PropertyInvestment HomeBuying RealEstateMarketing'],
};

// Suggested set size per platform (editorial guidance, not a platform rule).
const SIZE = { linkedin: 5, instagram: 15, tiktok: 6, x: 3 };
const NOTE = {
  linkedin: 'On LinkedIn, a few specific hashtags at the end of the post read better than a long list.',
  instagram: 'Instagram allows up to 30 hashtags. Mix broad and niche tags and rotate them between posts.',
  tiktok: 'On TikTok, pair one or two broad tags with specific ones that describe the video.',
  x: 'On X, one to three hashtags keep the post readable.',
};

const camel = (words) => words.map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join('');

tool('hashtag-generator', {
  validate: (v) => (v.topic && !/[a-z0-9]/i.test(v.topic) ? { topic: 'Use letters or numbers.' } : {}),
  run(v) {
    const parts = v.topic.toLowerCase().replace(/[^a-z0-9\s]/g, ' ').split(/\s+/).filter((w) => w.length > 1);
    const stop = new Set(['and', 'for', 'the', 'of', 'to', 'in', 'on', 'with', 'my', 'our', 'your', 'a', 'an']);
    const core = parts.filter((w) => !stop.has(w));
    const own = new Set();
    if (core.length) {
      own.add(camel(core.slice(0, 3)));
      core.forEach((w) => { if (w.length > 2) own.add(camel([w])); });
      if (core.length > 1) own.add(camel(core.slice(-2)));
      own.add(camel([...core.slice(0, 2), 'tips']));
    }
    const related = new Map();
    Object.values(LIST).forEach(([triggers, tags]) => {
      const t = triggers.split(' ');
      const score = core.filter((w) => t.includes(w) || t.some((x) => x.length > 3 && (w.startsWith(x) || x.startsWith(w)))).length;
      if (score) tags.split(' ').forEach((tag, i) => related.set(tag, Math.max(related.get(tag) || 0, score * 10 - i)));
    });
    own.forEach((t) => related.delete(t));
    const rel = [...related.entries()].sort((a, b) => b[1] - a[1]).map(([t]) => t).slice(0, 20);
    const size = SIZE[v.platform] || 5;
    const pick = [...own].slice(0, Math.ceil(size / 2));
    rel.forEach((t) => { if (pick.length < size && !pick.includes(t)) pick.push(t); });
    return { own: [...own], related: rel, pick, platform: v.platform, size };
  },
  render(r, out) {
    const tags = (list) => el('ul', { class: 'td-tool__tags', role: 'list' }, list.map((t) => el('li', { class: 'td-tool__tag', text: `#${t}` })));
    const join = (list) => list.map((t) => `#${t}`).join(' ');
    out.append(
      section(`Suggested set (${r.pick.length})`, el('p', { class: 'td-tool__p', text: NOTE[r.platform] || '' }), tags(r.pick), el('div', { class: 'td-tool__row' }, copyButton('Copy set', () => join(r.pick)))),
      section('From your topic', tags(r.own)),
    );
    if (r.related.length) out.append(section('Related', tags(r.related), el('div', { class: 'td-tool__row' }, copyButton('Copy all', () => join([...r.own, ...r.related])))));
    else out.append(section('Related', el('p', { class: 'td-tool__p', text: 'No related tags in our list for this topic yet. Try a broader word, like "marketing" or "startup".' })));
  },
  summary: (r) => r.pick.map((t) => `#${t}`).join(' '),
});
