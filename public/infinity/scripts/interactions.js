(function () {
  const dropdowns = document.querySelectorAll("[data-dropdown]");

  function closeDropdown(dropdown) {
    dropdown.classList.remove("is-open");
    const trigger = dropdown.querySelector(".dropdown__trigger");
    if (trigger) trigger.setAttribute("aria-expanded", "false");
  }

  function openDropdown(dropdown) {
    dropdown.classList.add("is-open");
    const trigger = dropdown.querySelector(".dropdown__trigger");
    if (trigger) trigger.setAttribute("aria-expanded", "true");
  }

  function toggleDropdown(dropdown) {
    const isOpen = dropdown.classList.contains("is-open");
    dropdowns.forEach(closeDropdown);
    if (!isOpen) openDropdown(dropdown);
  }

  dropdowns.forEach((dropdown) => {
    const trigger = dropdown.querySelector(".dropdown__trigger");
    const valueEl = dropdown.querySelector("[data-dropdown-value]");
    const options = Array.from(dropdown.querySelectorAll(".dropdown__option"));

    if (!trigger || options.length === 0) return;

    trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      toggleDropdown(dropdown);
    });

    options.forEach((option) => {
      option.addEventListener("click", (e) => {
        // Если это ссылка или кнопка с onclick - не меняем текст, страница перезагрузится
        if ((option.tagName === 'A' && option.href) || option.hasAttribute('onclick')) {
          closeDropdown(dropdown);
          return;
        }

        e.stopPropagation();
        // Используем textContent опции (переведенный текст), а не data-value
        const nextValue = option.textContent || "";
        if (valueEl) valueEl.textContent = nextValue.trim();

        options.forEach((opt) =>
          opt.setAttribute("aria-selected", opt === option ? "true" : "false")
        );
        closeDropdown(dropdown);
      });
    });
  });

  document.addEventListener("click", () => {
    dropdowns.forEach(closeDropdown);
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    dropdowns.forEach(closeDropdown);
  });
})();
