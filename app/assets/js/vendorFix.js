// Patch: Ensure asynchronous message listeners always call sendResponse

// Example: override a problematic message listener if defined in our context
if (chrome && chrome.runtime && chrome.runtime.onMessage) {
    const listeners = chrome.runtime.onMessage.hasListener || [];
    // Optionally, if you know the specific listener to patch, override it:
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
        // ...existing logic that may require async response...
        // Always call sendResponse to prevent the error:
        Promise.resolve()
            .then(() => {
                // Process the message as needed...
                const result = { status: "completed" };
                sendResponse(result);
            })
            .catch((err) => {
                sendResponse({ status: "error", error: err });
            });
        // Return true to indicate asynchronous response is pending
        return true;
    });
}
