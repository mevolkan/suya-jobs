document.addEventListener('DOMContentLoaded', function () {
    // Initialize the map without setting a specific view
    var map = L.map('projects-map');

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Project locations
    var projects = [
        { name: "PRM", country: "Kenya", county: "Baringo", lat: 0.4683, lng: 35.743, description: "PRM - Baringo" },
        { name: "PRM", country: "Kenya", county: "Wajir", lat: 1.7501, lng: 40.0658, description: "PRM - Wajir" },
        { name: "EASPO", country: "Kenya", lat: -1.286389, lng: 36.817223, description: "EASPO - Kenya" },
        { name: "EASPO", country: "Uganda", lat: 0.3476, lng: 32.5825, description: "EASPO - Uganda" },
        { name: "EASPO", country: "Tanzania", lat: -6.7924, lng: 39.2083, description: "EASPO - Tanzania" },
        { name: "GREENPARK", country: "Kenya", county: "Makueni", lat: -1.8037, lng: 37.6281, description: "GREENPARK - Makueni" },
        { name: "GREENPARK", country: "Kenya", county: "Kitui", lat: -1.3751, lng: 38.0108, description: "GREENPARK - Kitui" },
        { name: "MPCT", country: "Kenya", county: "Baringo", lat: 0.4683, lng: 35.743, description: "MPCT - Baringo" },
        { name: "SCALE", country: "Kenya", county: "Samburu", lat: 1.2656, lng: 36.7879, description: "SCALE - Samburu" },
        { name: "SCALE", country: "Kenya", county: "Turkana", lat: 3.3533, lng: 35.5732, description: "SCALE - Turkana" },
        { name: "ICREATE", country: "Kenya", county: "Baringo", lat: 0.4683, lng: 35.743, description: "ICREATE - Baringo" }
    ];

    // Create a bounds object
    var bounds = L.latLngBounds();

    // Add markers to the map and extend the bounds
    projects.forEach(function (project) {
        L.marker([project.lat, project.lng]).addTo(map)
            .bindPopup("<b>" + project.name + "</b><br>" + project.description);
        bounds.extend([project.lat, project.lng]);
    });

    // Fit the map to the bounds
    map.fitBounds(bounds, { padding: [50, 50] });
});