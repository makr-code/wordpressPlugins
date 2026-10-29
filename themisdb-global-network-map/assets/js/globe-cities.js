// Curated geographic reference points, not telemetry or an exhaustive population ranking.
export const MAJOR_CITIES = [
    { name: 'New York', lon: -74.006, lat: 40.7128, tier: 1 },
    { name: 'London', lon: -0.1278, lat: 51.5074, tier: 1 },
    { name: 'Tokyo', lon: 139.6917, lat: 35.6895, tier: 1 },
    { name: 'Paris', lon: 2.3522, lat: 48.8566, tier: 1 },
    { name: 'Singapore', lon: 103.8198, lat: 1.3521, tier: 1 },
    { name: 'Dubai', lon: 55.2708, lat: 25.2048, tier: 1 },
    { name: 'Beijing', lon: 116.4074, lat: 39.9042, tier: 1 },
    { name: 'Shanghai', lon: 121.4737, lat: 31.2304, tier: 1 },
    { name: 'Delhi', lon: 77.209, lat: 28.6139, tier: 1 },
    { name: 'Mumbai', lon: 72.8777, lat: 19.076, tier: 1 },
    { name: 'Los Angeles', lon: -118.2437, lat: 34.0522, tier: 1 },
    { name: 'Mexico City', lon: -99.1332, lat: 19.4326, tier: 1 },
    { name: 'Sao Paulo', lon: -46.6333, lat: -23.5505, tier: 1 },
    { name: 'Cairo', lon: 31.2357, lat: 30.0444, tier: 1 },
    { name: 'Lagos', lon: 3.3792, lat: 6.5244, tier: 1 },
    { name: 'Johannesburg', lon: 28.0473, lat: -26.2041, tier: 1 },
    { name: 'Sydney', lon: 151.2093, lat: -33.8688, tier: 1 },
    { name: 'Istanbul', lon: 28.9784, lat: 41.0082, tier: 1 },
    { name: 'Buenos Aires', lon: -58.3816, lat: -34.6037, tier: 1 },
    { name: 'Frankfurt', lon: 8.6821, lat: 50.1109, tier: 2 },
    { name: 'Berlin', lon: 13.405, lat: 52.52, tier: 2 },
    { name: 'Madrid', lon: -3.7038, lat: 40.4168, tier: 2 },
    { name: 'Rome', lon: 12.4964, lat: 41.9028, tier: 2 },
    { name: 'Moscow', lon: 37.6173, lat: 55.7558, tier: 2 },
    { name: 'Seoul', lon: 126.978, lat: 37.5665, tier: 2 },
    { name: 'Hong Kong', lon: 114.1694, lat: 22.3193, tier: 2 },
    { name: 'Bangkok', lon: 100.5018, lat: 13.7563, tier: 2 },
    { name: 'Jakarta', lon: 106.8456, lat: -6.2088, tier: 2 },
    { name: 'Manila', lon: 120.9842, lat: 14.5995, tier: 2 },
    { name: 'Karachi', lon: 67.0011, lat: 24.8607, tier: 2 },
    { name: 'Dhaka', lon: 90.4125, lat: 23.8103, tier: 2 },
    { name: 'Nairobi', lon: 36.8219, lat: -1.2921, tier: 2 },
    { name: 'Cape Town', lon: 18.4241, lat: -33.9249, tier: 2 },
    { name: 'Kinshasa', lon: 15.2663, lat: -4.4419, tier: 2 },
    { name: 'Toronto', lon: -79.3832, lat: 43.6532, tier: 2 },
    { name: 'Chicago', lon: -87.6298, lat: 41.8781, tier: 2 },
    { name: 'San Francisco', lon: -122.4194, lat: 37.7749, tier: 2 },
    { name: 'Washington', lon: -77.0369, lat: 38.9072, tier: 2 },
    { name: 'Lima', lon: -77.0428, lat: -12.0464, tier: 2 },
    { name: 'Bogota', lon: -74.0721, lat: 4.711, tier: 2 },
    { name: 'Santiago', lon: -70.6693, lat: -33.4489, tier: 2 },
    { name: 'Melbourne', lon: 144.9631, lat: -37.8136, tier: 2 },
    { name: 'Auckland', lon: 174.7633, lat: -36.8485, tier: 2 },
    { name: 'Amsterdam', lon: 4.9041, lat: 52.3676, tier: 3 },
    { name: 'Brussels', lon: 4.3517, lat: 50.8503, tier: 3 },
    { name: 'Zurich', lon: 8.5417, lat: 47.3769, tier: 3 },
    { name: 'Vienna', lon: 16.3738, lat: 48.2082, tier: 3 },
    { name: 'Munich', lon: 11.582, lat: 48.1351, tier: 3 },
    { name: 'Hamburg', lon: 9.9937, lat: 53.5511, tier: 3 },
    { name: 'Warsaw', lon: 21.0122, lat: 52.2297, tier: 3 },
    { name: 'Stockholm', lon: 18.0686, lat: 59.3293, tier: 3 },
    { name: 'Lisbon', lon: -9.1393, lat: 38.7223, tier: 3 },
    { name: 'Athens', lon: 23.7275, lat: 37.9838, tier: 3 },
    { name: 'Riyadh', lon: 46.6753, lat: 24.7136, tier: 3 },
    { name: 'Tel Aviv', lon: 34.7818, lat: 32.0853, tier: 3 },
    { name: 'Casablanca', lon: -7.5898, lat: 33.5731, tier: 3 },
    { name: 'Addis Ababa', lon: 38.7578, lat: 8.9806, tier: 3 },
    { name: 'Accra', lon: -0.187, lat: 5.6037, tier: 3 },
    { name: 'Seattle', lon: -122.3321, lat: 47.6062, tier: 3 },
    { name: 'Vancouver', lon: -123.1207, lat: 49.2827, tier: 3 },
    { name: 'Dallas', lon: -96.797, lat: 32.7767, tier: 3 },
    { name: 'Miami', lon: -80.1918, lat: 25.7617, tier: 3 },
    { name: 'Rio de Janeiro', lon: -43.1729, lat: -22.9068, tier: 3 },
    { name: 'Osaka', lon: 135.5023, lat: 34.6937, tier: 3 },
    { name: 'Taipei', lon: 121.5654, lat: 25.033, tier: 3 },
    { name: 'Shenzhen', lon: 114.0579, lat: 22.5431, tier: 3 },
    { name: 'Bengaluru', lon: 77.5946, lat: 12.9716, tier: 3 },
    { name: 'Perth', lon: 115.8605, lat: -31.9505, tier: 3 },
    { name: 'Wellington', lon: 174.7762, lat: -41.2866, tier: 3 },
];

export function rectanglesOverlap(a, b, gap = 4) {
    return a.left < b.right + gap && a.right + gap > b.left
        && a.top < b.bottom + gap && a.bottom + gap > b.top;
}

export function selectCityLabels(candidates, mode, width, height, obstacles = []) {
    const tier = { globe: 1, region: 2, country: 3 }[mode];
    const limit = Math.min({ globe: 12, region: 20, country: 30 }[mode], Math.max(4, Math.floor(width * height / 14000)));
    const selected = [];
    const occupied = [...obstacles];
    const ranked = candidates.filter(city => city.tier <= tier).sort((a, b) =>
        a.tier - b.tier || Number(Boolean(b.previouslyVisible)) - Number(Boolean(a.previouslyVisible)) || a.distance - b.distance || a.name.localeCompare(b.name));
    for (const city of ranked) {
        if (selected.length >= limit) break;
        const bounds = { left: city.x - 3, right: city.x + city.width, top: city.y - 10, bottom: city.y + 10 };
        if (bounds.left < 6 || bounds.right > width - 6 || bounds.top < 6 || bounds.bottom > height - 6
            || occupied.some(rect => rectanglesOverlap(bounds, rect))) continue;
        selected.push(city);
        occupied.push(bounds);
    }
    return selected;
}
