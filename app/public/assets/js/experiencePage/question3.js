const searchBar = document.getElementById('searchBar');
const searchMan = document.getElementById('searchMan');
const searchWoman = document.getElementById('searchWoman');
const searchResults = document.getElementById('searchResults');

// Add SSE URL configuration
const sseUrl = window.location.origin + '/api/performers_sse.php';

// Declare all state variables
let eventSource;
let selectedPerformerIds = new Set();
let includedPerformerIds = new Set();
let excludedPerformerIds = new Set();

// Constants
const DEBOUNCE_DELAY = 800;
const MIN_SEARCH_LENGTH = 2;

// Add performerDetails cache
window.performerDetailsCache = new Map();

function startSSE() {
    if (eventSource) {
        eventSource.close();
    }

    const searchTerm = searchBar.value.trim();
    const gender = getGenderFilter();

    // Don't search if term is too short
    if (searchTerm.length > 0 && searchTerm.length < MIN_SEARCH_LENGTH) {
        searchResults.innerHTML = '<div class="text-center py-4">Please enter at least 2 characters</div>';
        return;
    }

    // Show loading state immediately
    searchResults.innerHTML = '<div class="text-center py-4">Searching...</div>';

    try {

        if(!searchResults.classList.contains('py-4', 'bg-darkPrimairy/50')) {
            searchResults.classList.add('py-4', 'bg-darkPrimairy/50');
        }
        
        const params = new URLSearchParams({
            search: searchTerm,
            gender: getGenderFilter(),
            t: Date.now()
        });

        // Only start search if we have a search term or gender filter
        if (searchTerm.length === 0 && !getGenderFilter()) {
            searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4 bg-darkPrimairy/50">Type to search...</td></tr>';
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
        eventSource = new EventSource(sseUrl + '?' + params.toString());
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 3;

        // Cleanup function
        const cleanup = () => {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
        };

        // Add timeout to cancel long-running requests
        const timeoutId = setTimeout(() => {
            if (eventSource) {
                eventSource.close();
                searchResults.innerHTML = '<div class="text-center py-4">Search timed out. Please try again.</div>';
            }
        }, 5000);

        eventSource.onopen = () => {
            reconnectAttempts = 0;
            searchResults.innerHTML = '<tr><td colspan="2" class="text-center py-4 bg-darkPrimairy/50">Searching...</td></tr>';
        };

        eventSource.addEventListener('performers', (event) => {
            clearTimeout(timeoutId);
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
        searchResults.innerHTML = '<div class="text-center py-4">No results found</div>';
        return;
    }

    const defaultImage = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'white\' stroke-width=\'2\'%3E%3Ccircle cx=\'12\' cy=\'8\' r=\'5\'/%3E%3Cpath d=\'M3 21v-2a7 7 0 0 1 14 0v2\'/%3E%3C/svg%3E';
    
    try {
        // Use both current and cached selectedPerformerIds for filtering
        const cachedSelected = new Set(getCache('selectedPerformerIds') || []);
        const allSelectedIds = new Set([...selectedPerformerIds, ...cachedSelected]);
        
        const filteredData = data.filter(performer => {
            const performerId = performer.performer_id || performer.id;
            return !allSelectedIds.has(performerId);
        });
        
        // Preload first 5 images
        const preloadImages = filteredData.slice(0, 5).map(performer => {
            if (performer.image_url) {
                const img = new Image();
                img.src = performer.image_url;
                return img;
            }
            return null;
        });

        // Pre-fetch performer details and store in cache
        if (window.location.hostname.includes("localhost")) {
            fetch('/performers_details_data.json')
                .then(response => response.json())
                .then(details => {
                    filteredData.forEach(performer => {
                        const id = performer.performer_id || performer.id;
                        const detail = details.find(d => d.id === id);
                        if (detail) {
                            window.performerDetailsCache.set(id, detail);
                        }
                    });
                });
        }

        searchResults.classList.remove('py-4', 'bg-darkPrimairy/50');

        searchResults.innerHTML = `
            <ul class="divide-y divide-secondary">
                ${filteredData.map((performer, index) => {
                    let imageUrl = performer.image_url || defaultImage;
                    let performerId = performer.performer_id || performer.id;
                    let gender = performer.gender === 'Male' ? 'With penis' : 'Without a penis';
                    
                    return `
                        <li class="performer-row relative transition-colors bg-darkPrimairy/50 duration-300 hover:bg-secondary/20 text-start snap-center"
                            data-performer-id="${performerId || ''}"
                            data-performer-name="${performer.name || ''}"
                            data-performer-gender="${performer.gender}"
                            data-performer-info="${performer.extraInfo || ''}"
                            data-performer-image="${imageUrl}">
                            <div class="flex items-center justify-between p-4 cursor-pointer">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 rounded-[50%] overflow-hidden group/image flex-shrink-0">
                                        <img src="${index < 5 ? imageUrl : defaultImage}" 
                                             data-src="${imageUrl}"
                                             alt="${performer.name}" 
                                             class="w-full h-full blur-sm transition-all duration-300 group-hover/image:blur-none ${index >= 5 ? 'lazy' : ''}"
                                              style="clip-path: circle(50%);"
                                             loading="lazy">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-TextWhite truncate">
                                            ${performer.name}
                                        </p>
                                        <p class="text-xs text-secondary mt-1">
                                            ${gender}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" onclick="includePerformer('${performerId}','${performer.name}','${gender}', this)"
                                        class="border-2 border-tertery hover:bg-tertery/50 text-white px-3 py-1 rounded-full text-sm transition-colors duration-300">
                                        Include
                                    </button>
                                    <button type="button" onclick="excludePerformer('${performerId}','${performer.name}','${gender}', this)"
                                        class="border-2 border-secondaryTerteryMix hover:bg-secondaryTerteryMix/50  text-white px-3 py-1 rounded-full text-sm transition-colors duration-300">
                                        Exclude
                                    </button>
                                </div>
                            </div>
                        </li>
                    `;
                }).join('')}
            </ul>
        `;

        initializeLazyLoading();

        // NEW: Attach click event on performer rows for modal display
        document.querySelectorAll('.performer-row').forEach(row => {
            row.addEventListener('click', (e) => {
                // Avoid triggering if an include/exclude button is clicked
                if(e.target.closest('button')) return;
                const performer = {
                    id: row.dataset.performerId,
                    name: row.dataset.performerName,
                    gender: row.dataset.performerGender,
                    extraInfo: row.dataset.performerInfo,
                    image_url: row.dataset.performerImage
                };
                openPerformerModal(performer);
            });
        });

    } catch (error) {
        console.error('Error rendering results:', error);
        searchResults.innerHTML = '<div class="text-center py-4">Error displaying results</div>';
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
    const tagColor = type === 'Included' ? 'border-tertery' : 'border-secondaryTerteryMix';
    const cachedData = getCache('performerName_' + performerId);
    const [name, gender] = cachedData || [performerName, ''];
    
    container.insertAdjacentHTML('beforeend', `
        <div class="flex items-center space-x-2 px-3 py-1 bg-darkPrimairy/50 border-2 ${tagColor} rounded-full text-sm transition-all duration-300">
            <span>${name} (${type})</span>
            <button type="button" 
                    onclick="removeSelectedPerformer('${performerId}', this.parentElement, '${type}')"
                    class="ml-1 hover:text-TextWhite text-secondary transition-colors duration-300 flex items-center justify-center w-4 h-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
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

function includePerformer(performerId, performerName, performerGender, rowElement) {
    // Add to sets
    includedPerformerIds.add(performerId);
    selectedPerformerIds.add(performerId);
    
    // Create visual tag
    createTag(performerId, performerName, 'Included');
    
    // Remove from search results
    if (rowElement && rowElement.closest('li')) {
        rowElement.closest('li').remove();
    }
    
    // Update cache
    setCache('performerName_' + performerId, [performerName, performerGender]);
    setCache('includedPerformerIds', Array.from(includedPerformerIds));
    setCache('selectedPerformerIds', Array.from(selectedPerformerIds));
    forceUpdateQuestion4(); // trigger re-render in question4
}

function excludePerformer(performerId, performerName, performerGender, rowElement) {
    // Add to sets
    excludedPerformerIds.add(performerId);
    selectedPerformerIds.add(performerId);
    
    // Create visual tag
    createTag(performerId, performerName, 'Excluded');
    
    // Remove from search results
    if (rowElement && rowElement.closest('li')) {
        rowElement.closest('li').remove();
    }
    
    // Update cache
    setCache('performerName_' + performerId, [performerName, performerGender]);
    setCache('excludedPerformerIds', Array.from(excludedPerformerIds));
    setCache('selectedPerformerIds', Array.from(selectedPerformerIds));
    forceUpdateQuestion4(); // trigger re-render in question4
}

function removeSelectedPerformer(performerId, tagElement, type) {
    if (type === 'Included') {
        includedPerformerIds.delete(performerId);
    } else {
        excludedPerformerIds.delete(performerId);
    }
    selectedPerformerIds.delete(performerId);
    removeCache('performerName_' + performerId);
    tagElement.remove();
    
    // Update cache
    setCache('includedPerformerIds', Array.from(includedPerformerIds));
    setCache('excludedPerformerIds', Array.from(excludedPerformerIds));
    setCache('selectedPerformerIds', Array.from(selectedPerformerIds));
    forceUpdateQuestion4(); // trigger re-render in question4
    
    startSSE();
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

// Add this new function at the end
function updatePerformerCache() {
    setCache('includedPerformerIds', Array.from(includedPerformerIds));
    setCache('excludedPerformerIds', Array.from(excludedPerformerIds));
    setCache('selectedPerformerIds', Array.from(selectedPerformerIds));
}

// Initialize sets from cache
document.addEventListener('DOMContentLoaded', () => {
    const cachedIncluded = getCache('includedPerformerIds') || [];
    const cachedExcluded = getCache('excludedPerformerIds') || [];
    const cachedSelected = getCache('selectedPerformerIds') || [];

    includedPerformerIds = new Set(cachedIncluded);
    excludedPerformerIds = new Set(cachedExcluded);
    selectedPerformerIds = new Set(cachedSelected);

    // Recreate tags for cached performers
    cachedIncluded.forEach(id => {
        const name = getCache(`performerName_${id}`);
        if (name) createTag(id, name, 'Included');
    });
    
    cachedExcluded.forEach(id => {
        const name = getCache(`performerName_${id}`);
        if (name) createTag(id, name, 'Excluded');
    });

    // Initial search with cached filters
    startSSE();
});

function forceUpdateQuestion4() {
    window.dispatchEvent(new Event('myCustomUpdate'));
}

// Make functions globally accessible
window.includePerformer = includePerformer;
window.excludePerformer = excludePerformer;
window.removeSelectedPerformer = removeSelectedPerformer;