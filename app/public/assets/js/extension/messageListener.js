// A proper message listener that handles asynchronous responses
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    // ...existing code...
    someAsyncOperation(message)  // Example asynchronous function returning a Promise
        .then(result => {
            sendResponse({ success: true, data: result });
        })
        .catch(error => {
            sendResponse({ success: false, error: error.message });
        });
    return true; // Indicates that the response will be sent asynchronously
});
