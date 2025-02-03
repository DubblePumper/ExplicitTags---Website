document.addEventListener("DOMContentLoaded", () => {
  const peopleSummary = document.getElementById("peopleSummary");
  let dragDropInitialized = false;

  function loadSummaryData() {
    const counts = {
      withPenis: { min: parseInt(getCache("manMin")) || 0, max: parseInt(getCache("manMax")) || 0 },
      withoutPenis: { min: parseInt(getCache("womanMin")) || 0, max: parseInt(getCache("womanMax")) || 0 }
    };

    // Get all assigned performers
    const assignedPerformers = new Set();
    ["withPenis", "withoutPenis"].forEach((type) => {
      for (let i = 0; i < counts[type].max; i++) {
        const assigned = getCache(`assignment_${type}_${i}`);
        if (assigned) {
          assignedPerformers.add(assigned.performerId);
        }
      }
    });

    // Filter out assigned performers from included performers
    const includedPerformers = (getCache("includedPerformerIds") || [])
      .map((id) => {
        const data = getCache(`performerName_${id}`);
        return data ? { id, name: data[0], gender: data[1] } : null;
      })
      .filter((performer) => performer && !assignedPerformers.has(performer.id));

    const excludedPerformers = (getCache("excludedPerformerIds") || [])
      .map((id) => {
        const data = getCache(`performerName_${id}`);
        return data ? { id, name: data[0], gender: data[1] } : null;
      })
      .filter(Boolean);

    return { counts, includedPerformers, excludedPerformers };
  }

  function renderSummary() {
    const { counts, includedPerformers, excludedPerformers } = loadSummaryData();

    function renderPeopleGrid(count, type) {
      const imgSrc = type === "withPenis" ? "man_front.svg" : "woman_front.svg";
      const title = type === "withPenis" ? "People with penis" : "People without a penis";
      const range = `${counts[type].min}-${counts[type].max}`;

      if (counts[type].max === 0) return "";

      const images = Array(counts[type].max)
        .fill()
        .map((_, index) => {
          // Check if position has assigned performer
          const assigned = getCache(`assignment_${type}_${index}`);
          const assignedClass = assigned ? "opacity-20" : "";
          const assignedContent = assigned
            ? `
                    <div class="text-center relative w-full h-full flex flex-col items-center justify-center">
                        <button class="absolute top-5 right-5 -translate-y-1/2 translate-x-1/2 rounded-full p-1 remove-performer z-50 text-red-700 hover:text-white/50 transform transition-colors duration-300 ease-in-out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="font-medium text-TextWhite">${assigned.name}</div>
                        <div class="text-xs text-secondary mt-1">${assigned.gender}</div>
                    </div>
                `
            : "";

          return `
                    <div class="dropzone bg-darkPrimairy/30 p-4 rounded-lg transition-all duration-300 hover:bg-darkPrimairy/50 select-none h-[180px] flex items-center justify-center">
                        <div class="relative w-32 h-32 border-2 border-dashed border-secondary/50 rounded-lg flex items-center justify-center overflow-hidden">
                            <div class="absolute inset-0 bg-darkPrimairy/20 z-0"></div>
                            <img src="/assets/images/website_Images/${imgSrc}" 
                                 class="relative z-10 w-24 h-24 transition-all duration-300 group-hover:opacity-20 ${assignedClass}"
                                 data-position="${index}"
                                 data-type="${type}">
                            <div class="performer-placeholder absolute inset-0 z-20 flex items-center justify-center text-center p-2 bg-darkPrimairy/40">
                                ${assignedContent}
                            </div>
                        </div>
                    </div>
                `;
        })
        .join("");

      return `
                <div class="w-full mb-8">
                    <h3 class="text-lg font-semibold mb-4">${title} <span class="text-secondary">(${range})</span></h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        ${images}
                    </div>
                </div>
            `;
    }

    // Render performers as draggable cards
    function renderPerformerCards(performers, type) {
      if (!performers.length) return "";

      return `
                <div class="mt-6 bg-darkPrimairy/20 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-3">${type} Performers:</h3>
                    <div class="performers-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        ${performers
                          .map(
                            (p, index) => `
                            <div class="draggable bg-darkPrimairy/30 p-3 rounded-lg cursor-grab hover:shadow-lg 
                                       transition-all duration-300 active:scale-95 select-none touch-none"
                                 data-performer-id="${p.id}"
                                 data-performer-name="${p.name}"
                                 data-performer-gender="${p.gender}"
                                 data-grid-position="${index}"
                                 style="transform: translate(0px, 0px); grid-area: auto;">
                                <div class="flex items-center gap-3">
                                    <span class="font-medium">${p.name}</span>
                                    <span class="text-sm text-secondary">${p.gender}</span>
                                </div>
                            </div>
                        `
                          )
                          .join("")}
                    </div>
                </div>
            `;
    }

    peopleSummary.innerHTML = `
            <div class="space-y-6 w-full max-w-6xl p-6">
                ${renderPeopleGrid(counts.withPenis.max, "withPenis")}
                ${renderPeopleGrid(counts.withoutPenis.max, "withoutPenis")}
                ${renderPerformerCards(includedPerformers, "Required")}
            </div>
        `;

    // Only initialize drag and drop once
    if (!dragDropInitialized) {
      initializeDragAndDrop();
      dragDropInitialized = true;
    }
  }

  function formatGenderType(gender) {
    return gender.toLowerCase().split(" ").join("");
  }

  function checkValidDrop(type, performerGender) {
    const formattedGender = formatGenderType(performerGender);
    return (type === "withPenis" && formattedGender === "withpenis") || (type === "withoutPenis" && formattedGender === "withoutapenis");
  }

  function initializeDragAndDrop() {
    // Remove existing event listeners
    document.removeEventListener("click", handleRemovePerformer);

    // Add click handler for remove buttons
    document.addEventListener("click", handleRemovePerformer);

    interact(".draggable").draggable({
      inertia: true,
      modifiers: [
        interact.modifiers.restrict({
          restriction: ".performers-grid",
          endOnly: true
        })
      ],
      autoScroll: true,
      listeners: {
        start(event) {
          const element = event.target;
          element.classList.add("ring-2", "ring-secondary", "opacity-75", "z-50");
          // Store original position
          const rect = element.getBoundingClientRect();
          element.setAttribute("data-start-x", rect.left);
          element.setAttribute("data-start-y", rect.top);
        },
        move(event) {
          const { target } = event;
          const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
          const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;
          target.style.transform = `translate(${x}px, ${y}px)`;
          target.setAttribute("data-x", x);
          target.setAttribute("data-y", y);
        },
        end(event) {
          const element = event.target;
          element.classList.remove("ring-2", "ring-secondary", "opacity-75", "z-50");

          // If not dropped in a dropzone, animate back to grid position
          if (!document.querySelector(".dropzone.can-drop")) {
            element.style.transition = "all 0.3s ease";
            element.style.transform = "translate(0px, 0px)";
            element.setAttribute("data-x", 0);
            element.setAttribute("data-y", 0);

            // Remove transition after animation
            setTimeout(() => {
              element.style.transition = "";
            }, 300);
          }
        }
      }
    });

    interact(".dropzone").dropzone({
        
      accept: ".draggable",
      overlap: 0.5,
      ondropactivate: function (event) {
        const dropzone = event.target;
        const draggable = event.relatedTarget;
        const type = dropzone.querySelector("img").dataset.type;
        const performerGender = draggable.dataset.performerGender;
        const performerGenderFormatted = performerGender.toLowerCase().split(" ").join("");
        const placeholder = dropzone.querySelector(".performer-placeholder");

        // Check if empty or has remove button (can be replaced)
        const isEmpty = !placeholder.innerHTML || placeholder.querySelector(".remove-performer");

        // Check gender match
        const isValidDrop = checkValidDrop(type, performerGender);

        if (isEmpty && isValidDrop) {
          dropzone.classList.add("can-drop");
        }
      },
      ondropdeactivate: function (event) {
        const dropzone = event.target;
        dropzone.classList.remove("can-drop", "bg-red-500/20", "bg-green-500/20");
        dropzone.classList.remove("active-dropzone");
      },
      ondragenter: function (event) {
        const dropzone = event.target;
        const draggable = event.relatedTarget;
        const type = dropzone.querySelector("img").dataset.type;
        const performerGender = draggable.dataset.performerGender;
        
        dropzone.classList.add("active-dropzone");

        const isValidDrop = checkValidDrop(type, performerGender);

        // First remove any existing feedback classes
        dropzone.classList.remove("bg-red-500/20", "bg-green-500/20");

        if (isValidDrop) {
          dropzone.classList.add("bg-green-500/20");
          const placeholder = dropzone.querySelector(".performer-placeholder");
          placeholder.classList.add("bg-darkPrimairy/40");
          const image = dropzone.querySelector("img");
          if (image) image.classList.add("opacity-20", "scale-95");
        } else {
          dropzone.classList.add("bg-red-500/20");
          dropzone.classList.remove("can-drop");
        }
      },
      ondragleave: function (event) {
        const dropzone = event.target;
        dropzone.classList.remove("active-dropzone", "bg-red-500/20", "bg-green-500/20");
        const placeholder = dropzone.querySelector(".performer-placeholder");
        placeholder.classList.remove("bg-darkPrimairy/40");
        const image = dropzone.querySelector("img");
        if (image) {
          image.classList.remove("opacity-20", "scale-95");
        }
      },
      ondrop: function (event) {
        const draggable = event.relatedTarget;
        const dropzone = event.target;
        const image = dropzone.querySelector("img");
        const type = image.dataset.type;
        console.log("Type:", type);
        const placeholder = dropzone.querySelector(".performer-placeholder");
        const position = image.dataset.position;

        // Get performer data and update assignment
        const performerId = draggable.dataset.performerId;
        const performerName = draggable.dataset.performerName;
        const performerGender = draggable.dataset.performerGender;
        const performerGenderFormatted = performerGender.toLowerCase().split(" ").join("");

        console.log("performerGender:", performerGenderFormatted );

        // Validate gender match
        const isValidDrop = checkValidDrop(type, performerGender);

        if (!isValidDrop) {
          console.log("Invalid drop");
          return; // Prevent invalid drops
        }

        // If there's an existing assignment, remove it first
        const existingAssignment = getCache(`assignment_${type}_${position}`);
        if (existingAssignment) {
          setCache(`assignment_${type}_${position}`, null);
        }

        // Update cache first
        setCache(`assignment_${type}_${position}`, {
          performerId,
          name: performerName,
          gender: performerGender
        });

        // Update DOM directly instead of re-rendering
        image.classList.add("opacity-20");
        placeholder.classList.add("bg-darkPrimairy/40");
        placeholder.innerHTML = `
                    <div class="text-center relative w-full h-full flex flex-col items-center justify-center">
                        <button class="absolute top-5 right-5 -translate-y-1/2 translate-x-1/2 rounded-full p-1 remove-performer z-50 text-red-700 hover:text-white/50 transform transition-colors duration-300 ease-in-out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="font-medium text-TextWhite">${performerName}</div>
                        <div class="text-xs text-secondary mt-1">${performerGender}</div>
                    </div>
                `;

        // Remove the draggable element
        draggable.remove();
      }
    });
  }

  // Separate function for remove handler
  function handleRemovePerformer(e) {
    if (e.target.closest(".remove-performer")) {
      const dropzone = e.target.closest(".dropzone");
      const image = dropzone.querySelector("img");
      const position = image.dataset.position;
      const type = image.dataset.type;

      // Get the performer data before removing from cache
      const assignment = getCache(`assignment_${type}_${position}`);
      if (!assignment) return;

      // Remove assignment from cache
      setCache(`assignment_${type}_${position}`, null);

      // Update DOM directly for the dropzone
      image.classList.remove("opacity-20");
      const placeholder = dropzone.querySelector(".performer-placeholder");
      placeholder.innerHTML = "";
      placeholder.classList.remove("bg-darkPrimairy/40");

      // Force a complete re-render to properly update the Required performers grid
      renderSummary();
    }
  }

  // Initial render
  renderSummary();

  // Debounce the storage event handler
  let storageTimeout;
  window.addEventListener("storage", (e) => {
    if (e.key?.startsWith("performer") || e.key?.includes("Min") || e.key?.includes("Max")) {
      clearTimeout(storageTimeout);
      storageTimeout = setTimeout(() => {
        renderSummary();
      }, 100);
    }
  });

  window.addEventListener("myCustomUpdate", () => {
    renderSummary(); // re-render on custom event
  });
});
