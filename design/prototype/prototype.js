// Christlikeness — Phase 4C visual prototype. Disposable; not production code.
//
// CENTRES stands in for canonical centre records. The home visit strip, the Centres
// directory and the footer are all rendered from it, to test "edit once, show everywhere".
// Values come from the legacy harvest and are NOT church-confirmed (see
// docs/content-harvest/REVIEW-NEEDED.md R-01, R-02, R-19). Conflicting values are left
// empty and shown as pending, never chosen.

const CENTRES = [
  {
    slug: "north-york",
    name: "North York",
    schedule: [{ day: "Sunday", time: "10:00 AM" }],
    address: { street: "4544 Dufferin St.", unit: "Unit 210", locality: "North York, ON", postal: "L4K 5M5" },
    pending: [],
  },
  {
    slug: "scarborough",
    name: "Scarborough",
    schedule: [{ day: "Sunday", time: "2:00 PM" }],
    address: { street: "2220 Midland Ave.", unit: null, locality: "Scarborough, ON", postal: "M1P 3E6" },
    pending: ["Unit number pending confirmation"],
  },
];

const el = (tag, attrs = {}, ...children) => {
  const node = document.createElement(tag);
  for (const [key, value] of Object.entries(attrs)) {
    if (value == null) continue;
    if (key === "class") node.className = value;
    else node.setAttribute(key, value);
  }
  for (const child of children.flat()) {
    if (child == null) continue;
    node.append(child instanceof Node ? child : document.createTextNode(child));
  }
  return node;
};

const scheduleText = (centre) => centre.schedule.map((s) => `${s.day}s ${s.time}`).join(", ");
const streetLine = (centre) => [centre.address.street, centre.address.unit].filter(Boolean).join(", ");
const mapUrl = (centre) =>
  `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${centre.address.street} ${centre.address.locality}`)}`;

function renderVisitStrip(target) {
  target.replaceChildren(
    ...CENTRES.map((c) =>
      el("li", { class: "visit__item" },
        el("span", { class: "visit__name" }, c.name),
        el("span", { class: "visit__when" }, scheduleText(c)),
        el("span", { class: "visit__where meta" }, `${c.address.street}, ${c.address.locality}`),
      ),
    ),
  );
}

function renderDirectory(target) {
  const withPhotoSlots = new URLSearchParams(location.search).get("photos") === "slots";
  target.replaceChildren(
    ...CENTRES.map((c) =>
      el("li", { class: withPhotoSlots ? "centre centre--media" : "centre", id: c.slug },
        el("h2", { class: "centre__name" }, c.name),
        el("dl", { class: "centre__facts" },
          el("div", {},
            el("dt", {}, "Service"),
            el("dd", {}, ...c.schedule.map((s) => el("span", { style: "display:block" }, `${s.day}, ${s.time}`))),
          ),
          el("div", {},
            el("dt", {}, "Address"),
            el("dd", {},
              el("span", { style: "display:block" }, streetLine(c)),
              el("span", { style: "display:block" }, `${c.address.locality} ${c.address.postal}`),
              ...c.pending.map((p) => el("span", { class: "centre__pending" }, p)),
            ),
          ),
        ),
        el("div", { class: "actions" },
          el("a", { class: "button", href: "#plan-visit" }, `Plan a visit to ${c.name}`),
          el("a", { class: "text-link", href: mapUrl(c), rel: "noopener" }, "Get directions"),
        ),
        withPhotoSlots
          ? el("div", { class: "centre__media", role: "img", "aria-label": `Photo of the ${c.name} centre needed` }, `Centre photo needed: ${c.name}`)
          : null,
      ),
    ),
  );
}

function renderFooterCentres(target) {
  target.replaceChildren(
    ...CENTRES.map((c) =>
      el("li", { class: "footer__centre" },
        el("span", { class: "footer__centre-name" }, c.name),
        el("p", {}, scheduleText(c)),
        el("p", {}, `${streetLine(c)}, ${c.address.locality}`),
        el("p", {}, el("a", { href: mapUrl(c), rel: "noopener" }, "Directions")),
      ),
    ),
  );
}

function renderCounts() {
  const words = ["zero", "one", "two", "three", "four", "five", "six"];
  document.querySelectorAll("[data-centre-count]").forEach((node) => {
    node.textContent = words[CENTRES.length] ?? String(CENTRES.length);
  });
  document.querySelectorAll("[data-centre-names]").forEach((node) => {
    const names = CENTRES.map((c) => c.name);
    node.textContent = names.length > 1 ? `${names.slice(0, -1).join(", ")} and ${names.at(-1)}` : names[0];
  });
}

function applyFlags() {
  const params = new URLSearchParams(location.search);
  const root = document.documentElement;
  root.dataset.fonts = params.get("fonts") === "2" ? "2" : "3";
  root.dataset.night = params.get("night") === "evergreen" ? "evergreen" : "night";
  root.dataset.motion = params.get("motion") === "off" ? "off" : "on";
  if (params.has("shot")) root.dataset.shot = "1";

  const toolbar = document.querySelector(".proto-toolbar");
  if (!toolbar) return;
  const toggle = (key, on, off, label) => {
    const next = new URLSearchParams(params);
    const active = params.get(key) === on;
    if (active) next.delete(key); else next.set(key, on);
    const link = el("a", { href: `?${next}`, "aria-pressed": String(active) }, label);
    toolbar.append(link);
  };
  toggle("fonts", "2", null, "Two fonts");
  toggle("night", "evergreen", null, "Evergreen");
  toggle("motion", "off", null, "Motion off");
  if (document.querySelector("[data-render='directory']")) toggle("photos", "slots", null, "Photo slots");
}

function setupMenu() {
  const openButton = document.querySelector(".menu-toggle");
  const overlay = document.getElementById("menu");
  if (!openButton || !overlay) return;
  const closeButton = overlay.querySelector(".menu-close");

  const close = () => {
    overlay.hidden = true;
    openButton.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
    openButton.focus();
  };
  openButton.addEventListener("click", () => {
    overlay.hidden = false;
    openButton.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
    overlay.querySelector("a, button").focus();
  });
  closeButton.addEventListener("click", close);
  overlay.addEventListener("keydown", (event) => {
    if (event.key === "Escape") close();
  });
}

applyFlags();
document.querySelectorAll("[data-render='visit']").forEach(renderVisitStrip);
document.querySelectorAll("[data-render='directory']").forEach(renderDirectory);
document.querySelectorAll("[data-render='footer-centres']").forEach(renderFooterCentres);
renderCounts();
setupMenu();
