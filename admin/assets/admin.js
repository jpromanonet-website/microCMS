(() => {
  const body = document.documentElement;
  const themeToggle = document.getElementById("theme-toggle");

  const syncLabel = () => {
    if (!themeToggle) return;
    const isDark = body.getAttribute("data-theme") === "dark";
    themeToggle.setAttribute("aria-label", isDark ? "Switch to light theme" : "Switch to dark theme");
  };

  const stored = localStorage.getItem("jpr-theme");
  if (stored === "dark" || stored === "light") {
    body.setAttribute("data-theme", stored);
  } else if (!body.getAttribute("data-theme")) {
    body.setAttribute("data-theme", "light");
  }
  syncLabel();

  themeToggle?.addEventListener("click", () => {
    const next = body.getAttribute("data-theme") === "dark" ? "light" : "dark";
    body.setAttribute("data-theme", next);
    localStorage.setItem("jpr-theme", next);
    syncLabel();
  });

  const nav = document.getElementById("admin-nav");
  const navToggle = document.getElementById("nav-toggle");
  const setNavOpen = (open) => {
    document.body.classList.toggle("nav-open", open);
    if (navToggle) {
      navToggle.setAttribute("aria-expanded", open ? "true" : "false");
      navToggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    }
  };
  navToggle?.addEventListener("click", () => {
    setNavOpen(!document.body.classList.contains("nav-open"));
  });
  nav?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => setNavOpen(false));
  });
  window.addEventListener("resize", () => {
    if (window.innerWidth > 820) setNavOpen(false);
  });

  document.querySelectorAll("[data-file-drop]").forEach((drop) => {
    const input = drop.querySelector('input[type="file"]');
    const nameEl = drop.querySelector("[data-file-name]");
    if (!input || !nameEl) return;

    const emptyText = nameEl.getAttribute("data-empty") || "No file selected";

    const syncName = () => {
      const file = input.files && input.files[0];
      nameEl.textContent = file ? file.name : emptyText;
      drop.classList.toggle("has-file", Boolean(file));
    };

    input.addEventListener("change", syncName);
    ["dragenter", "dragover"].forEach((evt) => {
      drop.addEventListener(evt, (e) => {
        e.preventDefault();
        drop.classList.add("is-dragover");
      });
    });
    ["dragleave", "drop"].forEach((evt) => {
      drop.addEventListener(evt, (e) => {
        e.preventDefault();
        drop.classList.remove("is-dragover");
      });
    });
    drop.addEventListener("drop", (e) => {
      const files = e.dataTransfer?.files;
      if (!files || !files.length) return;
      try {
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        input.files = dt.files;
      } catch (_) {
        // Some browsers block programmatic FileList assignment; click browse instead.
      }
      syncName();
    });
    syncName();
  });

  document.querySelectorAll("[data-list-editor]").forEach((editor) => {
    const rows = editor.querySelector("[data-list-rows]");
    const addBtn = editor.querySelector("[data-list-add]");
    if (!rows || !addBtn) return;

    const makeRow = (name, placeholder, value = "") => {
      const template = editor.querySelector("template[data-list-template]");
      if (template?.content?.firstElementChild) {
        return template.content.firstElementChild.cloneNode(true);
      }
      const row = document.createElement("div");
      row.className = "list-editor__row";
      row.innerHTML =
        `<input type="text" name="${name}" value="" placeholder="${placeholder}" />` +
        `<button type="button" class="list-editor__remove" data-list-remove aria-label="Remove">&times;</button>`;
      row.querySelector("input").value = value;
      return row;
    };

    addBtn.addEventListener("click", () => {
      const name = addBtn.getAttribute("data-list-name") || "items[]";
      const placeholder = addBtn.getAttribute("data-list-placeholder") || "Item";
      const row = makeRow(name, placeholder);
      rows.appendChild(row);
      const focusable = row.querySelector("input, textarea, select");
      focusable?.focus();
    });

    rows.addEventListener("click", (event) => {
      const btn = event.target.closest("[data-list-remove]");
      if (!btn) return;
      const row = btn.closest("tr, .list-editor__row");
      if (!row || !rows.contains(row)) return;
      const allRows = rows.matches("tbody")
        ? rows.querySelectorAll(":scope > tr")
        : rows.querySelectorAll(":scope > .list-editor__row");
      if (allRows.length <= 1) {
        row.querySelectorAll("input, textarea").forEach((input) => {
          input.value = "";
        });
        row.querySelector("input, textarea")?.focus();
        return;
      }
      row.remove();
    });

    rows.addEventListener("keydown", (event) => {
      if (event.key !== "Enter") return;
      const input = event.target.closest("input");
      if (!input) return;
      event.preventDefault();
      addBtn.click();
    });
  });
})();
