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

    if (!trigger || !valueEl || options.length === 0) return;

    trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      toggleDropdown(dropdown);
    });

    options.forEach((option) => {
      option.addEventListener("click", (e) => {
        e.stopPropagation();
        const nextValue =
          option.getAttribute("data-value") || option.textContent || "";
        valueEl.textContent = nextValue.trim();

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
