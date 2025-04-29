document.addEventListener("DOMContentLoaded", function() {
    const table = document.querySelector("table tbody");
    const tableRows = Array.from(table.rows); // Convert table rows to array for sorting and filtering
    const searchBar = document.getElementById("searchBar");

    const headers = {
        siteName: document.getElementById("siteNameHeader"),
        url: document.getElementById("urlHeader"),
        status: document.getElementById("statusHeader"),
        checkType: document.getElementById("checkTypeHeader"),
        date: document.getElementById("dateHeader"),
    };

    const arrows = {
        siteName: document.getElementById("siteNameArrow"),
        url: document.getElementById("urlArrow"),
        status: document.getElementById("statusArrow"),
        checkType: document.getElementById("checkTypeArrow"),
        date: document.getElementById("dateArrow"),
    };

    let sortOrder = {
        siteName: "desc",
        url: "desc",
        status: "desc",
        checkType: "desc",
        date: "desc",
    };

    // Sort function
    function sortTable(index, key) {
        const direction = sortOrder[key];
        tableRows.sort((rowA, rowB) => {
            const valueA = rowA.cells[index].textContent.trim().toLowerCase();
            const valueB = rowB.cells[index].textContent.trim().toLowerCase();

            if (direction === "asc") {
                return valueA > valueB ? 1 : -1;
            } else {
                return valueA < valueB ? 1 : -1;
            }
        });

        // Reorder the rows in the table based on the sorted array
        tableRows.forEach(row => table.appendChild(row)); // Append row after sorting

        // Toggle sort order for next click
        sortOrder[key] = direction === "asc" ? "desc" : "asc";

        // Update arrow direction
        updateArrowDirection(key);
    }

    // Update arrow direction
    function updateArrowDirection(key) {
        for (const arrow in arrows) {
            arrows[arrow].textContent = "↓"; // Reset all arrows to down
        }
        arrows[key].textContent = sortOrder[key] === "asc" ? "↑" : "↓"; // Toggle arrow based on sort order
    }

    // Attach event listeners for column sorting
    headers.siteName.addEventListener("click", () => sortTable(0, "siteName"));
    headers.url.addEventListener("click", () => sortTable(1, "url"));
    headers.status.addEventListener("click", () => sortTable(2, "status"));
    headers.checkType.addEventListener("click", () => sortTable(3, "checkType"));
    headers.date.addEventListener("click", () => sortTable(4, "date"));

    // Search functionality
    searchBar.addEventListener("input", function() {
        const searchTerm = searchBar.value.toLowerCase();

        tableRows.forEach(row => {
            const siteName = row.querySelector("td:nth-child(1)").textContent.toLowerCase();
            const url = row.querySelector("td:nth-child(2)").textContent.toLowerCase();
            const status = row.querySelector("td:nth-child(3)").textContent.toLowerCase();

            // Only check the columns that we want to search (Site Name, URL, and Status)
            const matchesSearchTerm = siteName.includes(searchTerm) || url.includes(searchTerm) || status.includes(searchTerm);

            if (matchesSearchTerm) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });
});
