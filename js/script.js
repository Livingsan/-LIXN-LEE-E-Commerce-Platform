function searchProduct() {
    let query = document.getElementById("searchInput").value.trim();

    if (query === "") {
        alert("Please enter a search query.");
        return;
    }

    console.log("Searching for:", query); // Debugging log

    fetch(`http://localhost/shopping cart/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            console.log("Response received:", data); // Debugging log

            let resultsDiv = document.getElementById("results");
            resultsDiv.innerHTML = "";

            if (data.error) {
                resultsDiv.innerHTML = `<p style="color: red;">Error: ${data.error}</p>`;
                return;
            }

            if (data.length === 0) {
                resultsDiv.innerHTML = "<p>No products found.</p>";
                return;
            }

            data.forEach(product => {
                let productDiv = document.createElement("div");
                productDiv.innerHTML = `
                    <h3>${product.name}</h3>
                    <p>Size: ${product.size} | Category: ${product.category}</p>
                    <p>Price: $${product.price}</p>
                    <img src="${product.image_url}" width="100">
                    <hr>
                `;
                resultsDiv.appendChild(productDiv);
            });
        })
        .catch(error => {
            console.error("Fetch error:", error);
            document.getElementById("results").innerHTML = `<p style="color: red;">Failed to fetch data.</p>`;
        });
}

