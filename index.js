// Helper Functions //

export function xhrPostRequest(requestUrl, params, responseHandlerCallback) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", requestUrl);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = () => {
        console.log("Server response:", xhr.responseText);
        const responseObj = JSON.parse(xhr.responseText);
        responseHandlerCallback?.(responseObj);
    }
    xhr.onerror = function () {
        console.log("Request failed.");
    };

    let data = "";
    for (const [key, value] of Object.entries(params)) {
        if (data.length > 0) {
            data += "&"
        }
        data += key + "=" + encodeURIComponent(value);
    }

    xhr.send(data);
}
