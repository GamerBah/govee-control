importScripts("https://unpkg.com/axios/dist/axios.min.js");

self.onmessage = async (e) => {
    const {php, apiKey, deviceObj, url} = JSON.parse(e.data);

    try {
        const response = await axios(php, {
            url: php,
            method: "POST",
            params: {"api_key": apiKey, "api_url": url},
            headers: {"Content-Type": "application/json"},
            data: {
                requestId: "uuid",
                payload: {
                    sku: deviceObj.sku,
                    device: deviceObj.addr,
                },
            },
        });

        const data = {response: response.data, deviceObj: deviceObj};

        self.postMessage(data);

    } catch (error) {
        console.log("Error:", error);
    }
}