// Test API Redirect Logic
const testCases = [
    'http://localhost/teacher-eval/api/teachers.php',
    'http://127.0.0.1/teacher-eval/api/teachers.php',
    'http://192.168.0.243/teacher-eval/api/teachers.php',
    'http://192.168.8.33/teacher-eval/api/teachers.php',
    'https://example.com/teacher-eval/api/teachers.php',
    'http://localhost:8080/teacher-eval/api/teachers.php'
];

const API_BASE = '/teacher-eval';

console.log('Testing API Redirect Logic\n');
console.log('='.repeat(70));

testCases.forEach(url => {
    const absUrlMatch = url.match(/^https?:\/\/([^\/]+)(\/teacher-eval)?(.*)$/i);
    if (absUrlMatch) {
        const requestedHost = absUrlMatch[1];
        const basePath = absUrlMatch[2] || '';
        const requestPath = absUrlMatch[3];
        
        if (requestPath.includes('/api/')) {
            const newUrl = API_BASE + requestPath;
            console.log(`✅ FROM: ${url}`);
            console.log(`   TO:   ${newUrl}`);
            console.log(`   HOST: ${requestedHost}`);
            console.log();
        } else {
            console.log(`❌ FROM: ${url}`);
            console.log(`   NO API PATH FOUND`);
            console.log();
        }
    } else {
        console.log(`❌ FROM: ${url}`);
        console.log(`   NO MATCH`);
        console.log();
    }
});

console.log('='.repeat(70));
console.log('\nAll URLs should convert to /teacher-eval/api/... paths');
