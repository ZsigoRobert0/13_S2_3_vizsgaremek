using System.Net.Http.Headers;
using System.Text;
using Newtonsoft.Json;

namespace StockMaster.Wpf.Services
{
    public class ApiClient
    {
        private readonly HttpClient _http;
        public string JwtToken { get; private set; }

        public ApiClient(string baseUrl)
        {
            _http = new HttpClient { BaseAddress = new Uri(baseUrl) };
        }

        public void SetToken(string token)
        {
            JwtToken = token;
            _http.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", token);
        }

        public async Task<bool> LoginAsync(string email, string password)
        {
            var content = new StringContent(JsonConvert.SerializeObject(new { email, password }), Encoding.UTF8, "application/json");
            var res = await _http.PostAsync("api/auth/login", content);
            if (!res.IsSuccessStatusCode) return false;
            var body = JsonConvert.DeserializeObject<dynamic>(await res.Content.ReadAsStringAsync());
            SetToken((string)body.token);
            return true;
        }

        // Other methods: GetAssetsAsync, OpenPositionAsync, ClosePositionAsync, GetPortfolioAsync...
    }
}
