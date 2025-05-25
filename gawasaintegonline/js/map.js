/**
 * Map functionality for the profile page
 * Allows users to select their location on a map
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if map container exists on the page
    const mapContainer = document.getElementById('map-container');
    if (!mapContainer) return;
    
    // Map configuration
    const defaultLat = 14.5995; // Default to Philippines (can be adjusted)
    const defaultLng = 120.9842;
    const defaultZoom = 13;
    
    // Get saved coordinates from hidden fields if available
    let initialLat = document.getElementById('latitude').value || defaultLat;
    let initialLng = document.getElementById('longitude').value || defaultLng;
    
    // Convert string values to numbers
    initialLat = parseFloat(initialLat);
    initialLng = parseFloat(initialLng);
    
    // Create map
    const map = L.map('map').setView([initialLat, initialLng], defaultZoom);
    
    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add geocoding control (address search)
    const geocoder = L.Control.geocoder({
        defaultMarkGeocode: false,
        placeholder: 'Search for address...',
        errorMessage: 'Address not found',
        showResultIcons: true
    }).addTo(map);
    
    // Create marker for selected location
    let marker;
    
    // If we have saved coordinates, show the marker
    if (initialLat && initialLng && initialLat !== defaultLat && initialLng !== defaultLng) {
        marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
        updateAddressFromCoordinates(initialLat, initialLng);
    }
    
    // Handle geocoding results (when user searches for an address)
    geocoder.on('markgeocode', function(e) {
        const result = e.geocode || e;
        const latlng = result.center;
        
        // Set the map view to the result location
        map.setView(latlng, 16);
        
        // Update or create marker
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
        }
        
        // Update form fields with the new location
        updateFormFields(latlng.lat, latlng.lng);
        
        // Update address field
        updateAddressFromCoordinates(latlng.lat, latlng.lng);
    });
    
    // Handle map clicks - allow users to select location by clicking on map
    map.on('click', function(e) {
        const latlng = e.latlng;
        console.log('Map clicked at:', latlng.lat, latlng.lng);
        
        // Update or create marker
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            
            // Add drag events to new marker
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                updateFormFields(pos.lat, pos.lng);
                updateAddressFromCoordinates(pos.lat, pos.lng);
            });
            
            marker.on('drag', function(e) {
                const pos = e.target.getLatLng();
                updateFormFields(pos.lat, pos.lng);
            });
        }
        
        // Update form fields with the new location
        updateFormFields(latlng.lat, latlng.lng);
        
        // Directly update address field with coordinates first (immediate feedback)
        const addressField = document.getElementById('address');
        addressField.value = `Location at coordinates: ${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        
        // Then try to get a better address via geocoding
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`)
            .then(response => response.json())
            .then(data => {
                if (data && data.display_name) {
                    addressField.value = data.display_name;
                }
            })
            .catch(err => {
                console.error('Fetch geocoding error:', err);
                // Already set a basic address above, so we're covered
            });
            
        // Show notification that location was selected
        showNotification('Location selected', 'success');
    });
    
    // Make marker draggable and update coordinates when it's moved
    if (marker) {
        // Update on dragend (when user finishes dragging)
        marker.on('dragend', function(e) {
            const latlng = e.target.getLatLng();
            updateFormFields(latlng.lat, latlng.lng);
            updateAddressFromCoordinates(latlng.lat, latlng.lng);
        });
        
        // Live updates while dragging
        marker.on('drag', function(e) {
            const latlng = e.target.getLatLng();
            updateFormFields(latlng.lat, latlng.lng);
            // Don't update address while dragging (would cause too many API calls)
        });
    }
    
    // Current location button functionality
    const currentLocationBtn = document.getElementById('current-location-btn');
    if (currentLocationBtn) {
        currentLocationBtn.addEventListener('click', function() {
            if ("geolocation" in navigator) {
                // Show loading state
                currentLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
                currentLocationBtn.disabled = true;
                
                try {
                    navigator.geolocation.getCurrentPosition(
                        // Success callback
                        function(position) {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            
                            console.log('Got coordinates:', lat, lng);
                            
                            // Set map view to current location
                            map.setView([lat, lng], 16);
                            
                            // Update or create marker
                            if (marker) {
                                marker.setLatLng([lat, lng]);
                            } else {
                                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                                
                                // Add dragend event listener to the new marker
                                marker.on('dragend', function(e) {
                                    const latlng = e.target.getLatLng();
                                    updateFormFields(latlng.lat, latlng.lng);
                                    updateAddressFromCoordinates(latlng.lat, latlng.lng);
                                });
                            }
                            
                            // Update form fields
                            updateFormFields(lat, lng);
                            
                            // Use a direct approach to set the address field first
                            const addressField = document.getElementById('address');
                            if (!addressField.value) {
                                addressField.value = `Location at coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                            }
                            
                            // Then try to get a better address via geocoding
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data && data.display_name) {
                                        addressField.value = data.display_name;
                                    }
                                })
                                .catch(err => {
                                    console.error('Fetch geocoding error:', err);
                                    // Already set a basic address above, so we're covered
                                });
                            
                            // Reset button
                            currentLocationBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Use My Current Location';
                            currentLocationBtn.disabled = false;
                            
                            // Show success notification
                            showNotification('Location successfully updated', 'success');
                        },
                        // Error callback
                        function(error) {
                            console.error("Error getting location:", error);
                            let errorMessage = "Could not get your location.";
                            
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMessage = "You denied the request for geolocation. Please check your browser settings and allow location access.";
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMessage = "Location information is unavailable. Please try selecting a location manually on the map.";
                                    break;
                                case error.TIMEOUT:
                                    errorMessage = "The request to get your location timed out. Please try again or select a location manually.";
                                    break;
                                default:
                                    errorMessage = "An unknown error occurred. Please try selecting a location manually on the map.";
                            }
                            
                            // Show error notification
                            showNotification(errorMessage, 'error');
                            
                            // Reset button
                            currentLocationBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Use My Current Location';
                            currentLocationBtn.disabled = false;
                        },
                        // Options - try with less strict settings
                        {
                            enableHighAccuracy: false,  // Less strict, might work better on some devices
                            timeout: 15000,            // Longer timeout
                            maximumAge: 60000          // Accept cached positions up to 1 minute old
                        }
                    );
                } catch (e) {
                    console.error('Exception in geolocation:', e);
                    showNotification('Error accessing location services. Please try selecting a location on the map instead.', 'error');
                    currentLocationBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Use My Current Location';
                    currentLocationBtn.disabled = false;
                }
            } else {
                showNotification("Geolocation is not supported by this browser.", "error");
            }
        });
    }
    
    // Update hidden form fields with coordinates
    function updateFormFields(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(6);
        document.getElementById('longitude').value = lng.toFixed(6);
    }
    
    // Use reverse geocoding to update address field based on coordinates
    function updateAddressFromCoordinates(lat, lng) {
        // Use Leaflet Control Geocoder's reverse method to get address
        const geocoder = L.Control.Geocoder.nominatim();
        geocoder.reverse({ lat, lng }, map.getMaxZoom(), results => {
            if (results && results.length > 0) {
                const addressField = document.getElementById('address');
                addressField.value = results[0].name;
            } else {
                // If geocoding fails, at least put the coordinates in the address field
                const addressField = document.getElementById('address');
                if (!addressField.value) {
                    addressField.value = `Location at coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                }
                console.log('Geocoding failed to return results, using coordinates instead');
            }
        }, error => {
            // Handle geocoding errors
            console.error('Geocoding error:', error);
            const addressField = document.getElementById('address');
            if (!addressField.value) {
                addressField.value = `Location at coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }
        });
    }
    
    // Show notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Add icon based on notification type
        let icon = 'check-circle';
        if (type === 'error') icon = 'exclamation-circle';
        if (type === 'info') icon = 'info-circle';
        
        notification.innerHTML = `<i class="fas fa-${icon}" style="margin-right: 5px;"></i> ${message}`;
        document.body.appendChild(notification);

        // Position and style the notification
        Object.assign(notification.style, {
            position: 'fixed',
            bottom: '20px',
            right: '20px',
            background: type === 'success' ? '#4CAF50' : type === 'error' ? '#F44336' : '#2196F3',
            color: 'white',
            padding: '12px 20px',
            borderRadius: '4px',
            boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
            zIndex: '1000',
            display: 'flex',
            alignItems: 'center',
            fontWeight: '500'
        });

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    // Fix map display issues when in hidden tabs
    const profileLink = document.querySelector('a[href="#profile"]');
    if (profileLink) {
        profileLink.addEventListener('click', function() {
            setTimeout(function() {
                map.invalidateSize();
            }, 100);
        });
    }
});
