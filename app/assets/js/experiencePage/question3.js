const searchBar = document.getElementById('searchBar');
const searchMan = document.getElementById('searchMan');
const searchWoman = document.getElementById('searchWoman');
const searchResults = document.getElementById('searchResults');

let eventSource;
let selectedPerformerIds = new Set();
let includedPerformerIds = new Set();
let excludedPerformerIds = new Set();

function startSSE() {
    if (eventSource) {
        eventSource.close();
    }

    try {
        const searchTerm = searchBar.value.trim();
        const params = new URLSearchParams({
            search: searchTerm,
            gender: getGenderFilter(),
            t: Date.now()
        });

        // Only start search if we have a search term or gender filter
        if (searchTerm.length === 0 && !getGenderFilter()) {
            searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Type to search...</td></tr>';
            return;
        }

        // Check if we're on localhost:8000
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('localhost')) {
            // Use local JSON file for localhost
            fetch('/performers_details_data.json')
                .then(response => response.json())
                .then(data => {
                    let filteredData = data.filter(performer => {
                        if (!performer.name) return false;
                        
                        const nameMatch = !searchTerm || 
                            performer.name.toLowerCase().includes(searchTerm.toLowerCase());
                        const genderMatch = !getGenderFilter() || 
                            performer.gender === getGenderFilter();
                        
                        return nameMatch && genderMatch;
                    });

                    filteredData = filteredData.slice(0, 15);
                    updateSearchResults(filteredData);
                })
                .catch(error => {
                    console.error('Search Error:', error);
                    searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Error loading results</td></tr>';
                });
            return;
        }

        // Create new EventSource
        eventSource = new EventSource(sseUrl);
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 3;

        // Cleanup function
        const cleanup = () => {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
        };

        eventSource.onopen = () => {
            reconnectAttempts = 0;
            searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Searching...</td></tr>';
        };

        eventSource.addEventListener('performers', (event) => {
            try {
                const data = JSON.parse(event.data);
                updateSearchResults(data);
                cleanup(); // Close connection after receiving data
            } catch (e) {
                console.error('Error parsing performers data:', e);
                searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Error loading results</td></tr>';
                cleanup();
            }
        });

        eventSource.onerror = (event) => {
            console.error('SSE Error:', event);
            if (eventSource && eventSource.readyState === EventSource.CLOSED) {
                if (reconnectAttempts < maxReconnectAttempts) {
                    searchResults.innerHTML = `<tr><td colspan="2" class="text-center py-4">Reconnecting... (${reconnectAttempts + 1}/${maxReconnectAttempts})</td></tr>`;
                    reconnectAttempts++;
                    cleanup();
                    setTimeout(startSSE, 3000);
                } else {
                    searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Connection failed. Please try again later.</td></tr>';
                    cleanup();
                }
            }
        };

        // Add window unload handler
        window.addEventListener('unload', cleanup);

    } catch (error) {
        console.error('Error creating EventSource:', error);
        searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Connection error</td></tr>';
        if (eventSource) {
            eventSource.close();
        }
    }
}

function updateSearchResults(data) {
    if (!Array.isArray(data) || data.length === 0) {
        searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">No results found</td></tr>';
        return;
    }

    const defaultImage = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'white\' stroke-width=\'2\'%3E%3Ccircle cx=\'12\' cy=\'8\' r=\'5\'/%3E%3Cpath d=\'M3 21v-2a7 7 0 0 1 14 0v2\'/%3E%3C/svg%3E';
    
    try {
        const filteredData = data.filter(performer => !selectedPerformerIds.has(performer.performer_id));
        
        // Preload first 5 images
        const preloadImages = filteredData.slice(0, 5).map(performer => {
            if (performer.image_url) {
                const img = new Image();
                img.src = performer.image_url;
                return img;
            }
            return null;
        });

        searchResults.innerHTML = filteredData.map((performer, index) => {
            let imageUrl = performer.image_url || defaultImage;
            let performerId = performer.performer_id || performer.id;
            
            return `
                <tr class="performer-row cursor-pointer group relative transition-colors duration-300 hover:bg-secondary/20 border-b border-secondary"
                    data-performer-id="${performerId || ''}"
                    data-performer-name="${performer.name || ''}">
                    <td class="flex justify-center px-4 py-2 border-r border-secondary">
                        <div class="w-16 h-16 rounded-[50%] overflow-hidden">
                            <img src="${index < 5 ? imageUrl : defaultImage}" data-src="${imageUrl}"
                                 alt="${performer.name}" 
                                 class="w-full h-full blur-sm transition-all duration-300 group-hover:blur-none ${index >= 5 ? 'lazy' : ''}"
                                 style="clip-path: circle(50%);" loading="${index < 5 ? 'eager' : 'lazy'}">
                        </div>
                    </td>
                    <td class="px-4 py-2 text-end relative">
                        ${performer.name}
                        <!-- Buttons appear on row hover -->
                        <div class="hidden group-hover:flex absolute right-0 top-1/2 -translate-y-1/2 space-x-2">
                            <button type="button" onclick="includePerformer('${performerId}','${performer.name}', this)"
                                class="bg-green-600 text-white px-2 py-1 rounded">Include</button>
                            <button type="button" onclick="excludePerformer('${performerId}','${performer.name}', this)"
                                class="bg-red-600 text-white px-2 py-1 rounded">Exclude</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Initialize lazy loading for images
        initializeLazyLoading();

    } catch (error) {
        console.error('Error rendering results:', error);
        searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4">Error displaying results</td></tr>';
    }
}

// Add this new function for lazy loading
function initializeLazyLoading() {
    const lazyImages = document.querySelectorAll('img.lazy');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.1
    });

    lazyImages.forEach(img => imageObserver.observe(img));
}

function createTag(performerId, performerName, type) {
    const container = (type === 'Included') ? document.getElementById('taggsIncluded') : document.getElementById('taggsExcluded');
    container.insertAdjacentHTML('beforeend', `
        <div class="flex items-center space-x-2 px-3 py-1 bg-darkPrimairy border-2 border-secondary rounded-full text-sm transition-all duration-300 hover:border-primairy">
            <span>${performerName} (${type})</span>
            <button type="button" onclick="removeSelectedPerformer('${performerId}', this.parentElement, '${type}')"
                class="text-secondary hover:text-primairy transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    `);
}

function removePerformer(performerId, tagElement) {
    selectedPerformerIds.delete(performerId);
    tagElement.remove();
    // Refresh the search to show the removed performer
    startSSE();
}

function includePerformer(performerId, performerName, rowElement) {
    includedPerformerIds.add(performerId);
    createTag(performerId, performerName, 'Included');
    rowElement.closest('tr').remove();
}

function excludePerformer(performerId, performerName, rowElement) {
    excludedPerformerIds.add(performerId);
    createTag(performerId, performerName, 'Excluded');
    rowElement.closest('tr').remove();
}

function removeSelectedPerformer(performerId, tagElement, type) {
    if (type === 'Included') {
        includedPerformerIds.delete(performerId);
    } else {
        excludedPerformerIds.delete(performerId);
    }
    tagElement.remove();
    // Optionally refresh or do something else
}

function togglePerformerSelection(row) {
    try {
        const performerId = row.dataset.performerId;
        const performerName = row.dataset.performerName;
        
        if (!performerId) {
            console.warn('No performer ID found for:', performerName);
            return;
        }

        // Add to selected performers set
        selectedPerformerIds.add(performerId);
        
        // Add tag
        const tagsContainer = document.getElementById('taggs');
        tagsContainer.insertAdjacentHTML('beforeend', createTag(performerId, performerName));
        
        // Remove from search results
        row.remove();
        
        console.log('Selected performers:', Array.from(selectedPerformerIds));
    } catch (error) {
        console.error('Error toggling performer selection:', error);
    }
}

function getGenderFilter() {
    const isManChecked = searchMan.checked;
    const isWomanChecked = searchWoman.checked;
    
    if (isManChecked && !isWomanChecked) return 'Male';
    if (!isManChecked && isWomanChecked) return 'Female';
    return '';
}

searchBar.addEventListener('input', debounceSearch);
searchMan.addEventListener('change', () => {
    clearTimeout(debounceTimer);
    startSSE();
});
searchWoman.addEventListener('change', () => {
    clearTimeout(debounceTimer);
    startSSE();
});

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const searchTerm = searchBar.value.trim();
        if (searchTerm.length > 0 || getGenderFilter()) {
            startSSE();
        }
    }, 300);
}

window.addEventListener('DOMContentLoaded', startSSE);