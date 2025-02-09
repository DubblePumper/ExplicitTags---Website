import { performerDetailsCache } from '../experiencePage/question3.js';

export function openPerformerModal(performer) {
    let detailSource = null;

    // Build initial modal with a placeholder for details
    const modalHtml = `
        <div id="performerModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-darkPrimairy p-6 rounded-lg max-w-lg w-full relative">
                <button id="closeModal" class="absolute top-2 right-2 text-secondary hover:text-white transition duration-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="flex flex-col items-center space-y-4">
                    <img src="${performer.image_url || ""}" alt="${performer.name}" class="w-32 h-32 rounded-full">
                    <h2 class="text-xl font-bold text-TextWhite">${performer.name}</h2>
                    <p class="text-secondary">${performer.gender}</p>
                    <!-- Placeholder for detailed info -->
                    <div id="performerDetailContent" class="text-sm text-TextWhite">
                        Loading detailed info...
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML("beforeend", modalHtml);
    const modal = document.getElementById("performerModal");
    
    // Add bio toggle handler
    modal.addEventListener('click', (e) => {
        if (e.target.classList.contains('bio-toggle')) {
            const container = e.target.closest('.bio-container');
            const shortBio = container.querySelector('.bio-short');
            const fullBio = container.querySelector('.bio-full');
            
            shortBio.classList.toggle('hidden');
            fullBio.classList.toggle('hidden');
            e.target.textContent = shortBio.classList.contains('hidden') ? 'Show less' : 'Show more';
        }
        
        const isCloseButton = e.target.closest('#closeModal') || e.target.id === "performerModal";
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
        return;
    }

    // If not in cache, fetch from source
    if (window.location.hostname.includes("localhost")) {
        fetch('/performers_details_data.json')
            .then(response => response.json())
            .then(data => {
                const detailData = data.find(item => item.id === performer.id);
                if (detailData) {
                    performerDetailsCache.set(performer.id, detailData);
                    const detailContent = document.getElementById("performerDetailContent");
                    detailContent.innerHTML = generateDetailHtml(detailData);
                } else {
                    const detailContent = document.getElementById("performerDetailContent");
                    detailContent.innerHTML = `<p>No detailed info found.</p>`;
                }
            })
            .catch(error => {
                console.error("Error fetching local performer details:", error);
                const detailContent = document.getElementById("performerDetailContent");
                detailContent.innerHTML = `<p>Error loading details.</p>`;
            });
    } else {
        // SSE: Request detailed performer info (for production or non-localhost)
        detailSource = new EventSource(window.location.origin + "/api/performer_detail_sse.php?performer_id=" + encodeURIComponent(performer.id));
        detailSource.addEventListener("performer_detail", (e) => {
            try {
                const detailData = JSON.parse(e.data);
                const detailContent = document.getElementById("performerDetailContent");
                detailContent.innerHTML = generateDetailHtml(detailData);
                performerDetailsCache.set(performer.id, detailData);
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

// Helper function to generate detail HTML
function generateDetailHtml(data) {
    return `
        ${data.birthday ? `<p><strong>Birthday:</strong> ${data.birthday}</p>` : ""}
        ${data.ethnicity ? `<p><strong>Ethnicity:</strong> ${data.ethnicity}</p>` : ""}
        ${data.nationality ? `<p><strong>Nationality:</strong> ${data.nationality}</p>` : ""}
        ${data.hair_color ? `<p><strong>Hair:</strong> ${data.hair_color}</p>` : ""}
        ${data.eye_color ? `<p><strong>Eyes:</strong> ${data.eye_color}</p>` : ""}
        ${data.measurements ? `<p><strong>Measurements:</strong> ${data.measurements}</p>` : ""}
        ${data.image_amount ? `<p><strong>Images:</strong> ${data.image_amount}</p>` : ""}
    `;
}
