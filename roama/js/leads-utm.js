/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Where a visitor came from, for the leads (inc/leads-core.php roama_leads_source()):
// UTM tags, an outside referrer and the landing page in a first-party cookie for 90 days.
// The first visit writes it; a later visit with new UTM tags replaces it.
(() => {
  const NAME = 'roama_lead_source';
  const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
  const MAX = 200;
  const params = new URLSearchParams(window.location.search);
  const tagged = KEYS.some((key) => params.get(key));
  const known = document.cookie.split('; ').some((part) => part.startsWith(`${NAME}=`));
  if (known && !tagged) return;

  const data = new URLSearchParams();
  for (const key of KEYS) {
    const value = params.get(key);
    if (value) data.set(key, value.slice(0, MAX));
  }
  try {
    const from = document.referrer ? new URL(document.referrer) : null;
    if (from && from.host !== window.location.host && /^https?:$/.test(from.protocol)) {
      data.set('referrer', `${from.origin}${from.pathname}`.slice(0, MAX));
    }
  } catch {
    // An unreadable referrer is simply left out.
  }
  data.set('landing', `${window.location.pathname}${window.location.search}`.slice(0, MAX));
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${NAME}=${encodeURIComponent(data.toString())}; Max-Age=${90 * 24 * 60 * 60}; Path=/; SameSite=Lax${secure}`;
})();
