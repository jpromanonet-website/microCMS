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
})();
