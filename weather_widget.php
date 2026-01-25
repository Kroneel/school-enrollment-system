<?php
/*
====================================================
 Weather Widget – Suva, Fiji
----------------------------------------------------
 Purpose:
 - Fetch live daily weather data for Suva, Fiji
 - Display temperature and rain probability
 - Demonstrate external API integration
 - Use caching to reduce repeated API calls
====================================================
*/

// -------------------------------------------------
// 1) Location coordinates for Suva, Fiji
// Source: General geographic coordinates
// Latitude: -18.1248
// Longitude: 178.4501
// -------------------------------------------------
$latitude  = -18.1248;
$longitude = 178.4501;

// -------------------------------------------------
// 2) Open-Meteo API URL
// We request:
// - max temperature
// - min temperature
// - precipitation probability
// Timezone set to Pacific/Fiji
// -------------------------------------------------
$apiUrl = "https://api.open-meteo.com/v1/forecast"
        . "?latitude={$latitude}"
        . "&longitude={$longitude}"
        . "&daily=temperature_2m_max,temperature_2m_min,precipitation_probability_max"
        . "&timezone=Pacific%2FFiji";

// -------------------------------------------------
// 3) Simple caching configuration
// Cache file prevents too many API requests
// Cache duration: 10 minutes (600 seconds)
// -------------------------------------------------
$cacheFile    = __DIR__ . "/cache_weather.json";
$cacheSeconds = 600;

// Variable to store decoded API data
$weatherData = null;

// -------------------------------------------------
// 4) Load cached data if still valid
// -------------------------------------------------
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheSeconds)) {

    // Read cached JSON
    $json = file_get_contents($cacheFile);
    $weatherData = json_decode($json, true);

} else {

    // Fetch fresh data from Open-Meteo API
    $json = @file_get_contents($apiUrl);

    // If API call successful, save to cache
    if ($json !== false) {
        file_put_contents($cacheFile, $json);
        $weatherData = json_decode($json, true);
    }
}

// -------------------------------------------------
// 5) Extract today's weather values safely
// -------------------------------------------------
$dateToday   = null;
$tempMax     = null;
$tempMin     = null;
$rainChance  = null;

if (isset($weatherData["daily"]["time"][0])) {
    $dateToday  = $weatherData["daily"]["time"][0];
    $tempMax    = $weatherData["daily"]["temperature_2m_max"][0] ?? null;
    $tempMin    = $weatherData["daily"]["temperature_2m_min"][0] ?? null;
    $rainChance = $weatherData["daily"]["precipitation_probability_max"][0] ?? null;
}
?>

<!-- WEATHER CARD UI -->
<div class="card shadow-sm h-100">
  <div class="card-body">

    <!-- Card title -->
    <h5 class="fw-bold mb-2">Weather – Suva, Fiji</h5>

    <?php if ($dateToday && $tempMax !== null && $tempMin !== null) { ?>

      <!-- Weather data display -->
      <p class="text-muted small mb-2">
        Live weather forecast (via Open-Meteo)
      </p>

      <div class="d-flex flex-wrap gap-3">

        <!-- Date -->
        <div>
          <div class="text-muted small">Date</div>
          <div class="fw-semibold"><?= htmlspecialchars($dateToday) ?></div>
        </div>

        <!-- Temperature -->
        <div>
          <div class="text-muted small">Temperature</div>
          <div class="fw-semibold">
            <?= htmlspecialchars($tempMin) ?>°C – <?= htmlspecialchars($tempMax) ?>°C
          </div>
        </div>

        <!-- Rain probability -->
        <div>
          <div class="text-muted small">Rain Chance</div>
          <div class="fw-semibold">
            <?= $rainChance !== null ? htmlspecialchars($rainChance) . "%" : "N/A" ?>
          </div>
        </div>

      </div>

      <!-- Data source link -->
      <a href="https://open-meteo.com"
         target="_blank"
         class="btn btn-sm btn-outline-primary mt-3">
        Weather Data Source
      </a>

    <?php } else { ?>

      <!-- Fallback message if API fails -->
      <div class="alert alert-warning mb-0">
        Weather information is currently unavailable.
      </div>

    <?php } ?>

  </div>
</div>
