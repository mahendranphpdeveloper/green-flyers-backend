<script>
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            console.log("Lat:", pos.coords.latitude);
            console.log("Lon:", pos.coords.longitude);
        },
        (err) => {
            console.error(err);
        }
    );</script>
    