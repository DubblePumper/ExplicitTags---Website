import { performerDetailsCache } from "../experiencePage/question3.js";

// Add new helper function for image slider
function initializeImageSlider(imageContainer, images) {
    let currentIndex = 0;
    const validImages = images.filter(img => img && img !== "");

    if (!validImages.length) return;

    // Set first image
    const imgElement = imageContainer.querySelector('img');
    imgElement.src = validImages[0];
    
    // Auto rotate images
    const slideInterval = setInterval(() => {
        currentIndex = (currentIndex + 1) % validImages.length;
        imgElement.style.opacity = '0';
        
        setTimeout(() => {
            imgElement.src = validImages[currentIndex];
            imgElement.style.opacity = '1';
        }, 300); // Match transition duration
    }, 3000); // Change image every 3 seconds

    // Cleanup on modal close
    const modal = document.getElementById('performerModal');
    const originalCleanup = modal.onremove;
    modal.onremove = () => {
        clearInterval(slideInterval);
        if (originalCleanup) originalCleanup();
    };
}

export function openPerformerModal(performer) {
  let detailSource = null;

  // Build initial modal with a placeholder for details
  const modalHtml = `
        <div id="performerModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="bg-darkPrimairy p-6 rounded-lg w-full max-w-lg relative my-8">
                <button id="closeModal" class="absolute top-2 right-2 text-secondary hover:text-white transition duration-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="flex flex-col items-center w-full gap-4">
                    <!-- Profile image container with fixed dimensions -->
                    <div class="relative w-32 h-32 rounded-full overflow-hidden group cursor-pointer flex-shrink-0" id="performerImageContainer">
                        <img 
                            id="performerImage"
                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2'%3E%3Ccircle cx='12' cy='8' r='5'/%3E%3Cpath d='M3 21v-2a7 7 0 0 1 14 0v2'/%3E%3C/svg%3E" 
                            alt="${performer.name}" 
                            class="w-full h-full object-cover transition-all duration-300 blur-md group-hover:blur-none"
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <!-- Name and gender with consistent width -->
                    <div class="flex flex-col items-center gap-1 w-full">
                        <h2 id="name" class="text-xl font-bold text-TextWhite text-center">${performer.name}</h2>
                        <p id="gender" class="text-secondary text-center">${performer.gender}</p>
                    </div>
                    <!-- Details container with proper spacing -->
                    <div id="performerDetailContent" class="text-sm text-TextWhite w-full max-h-[60vh] overflow-y-auto px-4 py-2 space-y-4">
                        Loading detailed info...
                    </div>
                </div>
            </div>
        </div>
    `;

  document.body.insertAdjacentHTML("beforeend", modalHtml);
  const modal = document.getElementById("performerModal");

  // Add bio toggle handler
  modal.addEventListener("click", (e) => {
    if (e.target.classList.contains("bio-toggle")) {
      const container = e.target.closest(".bio-container");
      const shortBio = container.querySelector(".bio-short");
      const fullBio = container.querySelector(".bio-full");

      shortBio.classList.toggle("hidden");
      fullBio.classList.toggle("hidden");
      e.target.textContent = shortBio.classList.contains("hidden") ? "Show less" : "Show more";
    }

    const isCloseButton = e.target.closest("#closeModal") || e.target.id === "performerModal";
    if (isCloseButton) {
      modal.remove();
      if (detailSource) detailSource.close();
    }
  });

  // Check cache first
  const cachedDetails = performerDetailsCache.get(performer.id);
  if (cachedDetails) {
    const detailContent = document.getElementById("performerDetailContent");
    detailContent.innerHTML = generateDetailHtml(cachedDetails);
    initializeImages(cachedDetails);
    return;
  }

  // If not in cache, fetch from source
  if (window.location.hostname.includes("localhost")) {
    fetch("/performers_details_data.json")
      .then((response) => response.json())
      .then((data) => {
        // Ensure data is an array
        const detailsArray = Array.isArray(data) ? data : [data];
        const detailData = detailsArray.find((item) => item.id === performer.id);
        if (detailData) {
          performerDetailsCache.set(performer.id, detailData);
          const detailContent = document.getElementById("performerDetailContent");
          detailContent.innerHTML = generateDetailHtml(detailData);
          initializeImages(detailData);
        } else {
          const detailContent = document.getElementById("performerDetailContent");
          detailContent.innerHTML = `<p>No detailed info found.</p>`;
        }
      })
      .catch((error) => {
        console.error("Error fetching local performer details:", error);
        const detailContent = document.getElementById("performerDetailContent");
        detailContent.innerHTML = `<p>Error loading details.</p>`;
      });
  } else {
    // SSE: Request detailed performer info (for production or non-localhost)
    detailSource = new EventSource(window.location.origin + "/api/performer_detail_sse.php?performer_id=" + encodeURIComponent(performer.id));
    detailSource.addEventListener("performer_detail", (e) => {
      try {
        const data = JSON.parse(e.data);
        // Ensure data is an array and get first item
        const detailData = Array.isArray(data) ? data[0] : data;
        if (detailData) {
          const detailContent = document.getElementById("performerDetailContent");
          detailContent.innerHTML = generateDetailHtml(detailData);
          performerDetailsCache.set(performer.id, detailData);
          initializeImages(detailData);
        }
      } catch (error) {
        console.error("Error updating performer detail:", error);
      }
      detailSource.close();
    });
    detailSource.onerror = (e) => {
      console.error("Error fetching performer details:", e);
      detailSource.close();
    };
  }
}

// Fetch and initialize image slider or placeholder depending on environment
const initializeImages = (detailData) => {
    const imageContainer = document.getElementById('performerImageContainer');
    const defaultSvg = 'data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'white\' stroke-width=\'2\'><circle cx=\'12\' cy=\'8\' r=\'5\'/><path d=\'M3 21v-2a7 7 0 0 1 14 0v2\'/></svg>';

    if (window.location.hostname.includes('localhost')) {
        // Use static SVG placeholder for localhost
        const imgElement = imageContainer.querySelector('img');
        imgElement.src = defaultSvg;
        imgElement.style.opacity = '1';
    } else if (detailData && detailData.image_urls && Array.isArray(detailData.image_urls)) {
        // Use image slider for production
        initializeImageSlider(imageContainer, detailData.image_urls);
    }
};

// Helper function to generate detail HTML
function generateDetailHtml(data) {
    // Format date helper
    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === 'null' || dateStr.trim() === '') return 'N/A';
        try {
            return new Date(dateStr).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (e) {
            return dateStr;
        }
    };

    // Clean and format value helper
    const formatValue = (value) => {
        if (value === null || value === undefined || value === '') return 'N/A';
        if (typeof value === 'boolean') return value ? 'Yes' : 'No';
        return value;
    };

    // Group details by category
    const details = {
        basic: [
            { label: 'Rating', value: data.rating ? `${data.rating}/5` : 'N/A' },
            { label: 'Birthday', value: formatDate(data.birthday) },
            { label: 'Birthplace', value: formatValue(data.birthplace) },
            { label: 'Career', value: data.career_start_year ? 
                `${data.career_start_year} - ${formatValue(data.career_end_year)}` : 'N/A' }
        ],
        physical: [
            { label: 'Ethnicity', value: formatValue(data.ethnicity) },
            { label: 'Nationality', value: formatValue(data.nationality) },
            { label: 'Height', value: formatValue(data.height) },
            { label: 'Weight', value: formatValue(data.weight) },
            {
                label: 'Measurements',
                value: data.cup_size || data.waist_size || data.hip_size ? 
                    `<span class="group relative inline-block">
                        ${formatValue(data.cup_size)} - ${formatValue(data.waist_size)} - ${formatValue(data.hip_size)}
                        <div class="hidden group-hover:block absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-darkPrimairy border border-secondary rounded whitespace-nowrap">
                            Cup Size - Waist - Hips (in inches)
                        </div>
                    </span>` : 'N/A'
            },
            { label: 'Hair Color', value: formatValue(data.hair_color) },
            { label: 'Eye Color', value: formatValue(data.eye_color) }
        ],
        additional: [
            { label: 'Tattoos', value: formatValue(data.tattoos) },
            { label: 'Piercings', value: formatValue(data.piercings) },
            { label: 'Enhanced', value: formatValue(data.fake_boobs) },
            { label: 'Same Sex Only', value: formatValue(data.same_sex_only) },
            { label: 'Images', value: formatValue(data.image_amount) }
        ]
    };

    return `
        <div class="space-y-6">
            ${Object.entries(details).map(([category, items]) => `
                <div class="space-y-2">
                    <h3 class="text-lg font-medium capitalize border-b border-secondary/30 pb-1 mb-2">${category} Info</h3>
                    <div class="grid grid-cols-1 gap-2">
                        ${items.map(({ label, value }) => 
                            value !== 'N/A' ? 
                            `<div class="flex justify-between">
                                <span class="text-secondary">${label}:</span>
                                <span class="text-TextWhite text-end">${value}</span>
                            </div>` : ''
                        ).filter(Boolean).join('')}
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}
