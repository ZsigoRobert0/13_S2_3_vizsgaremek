using Microsoft.EntityFrameworkCore;
using StockMaster.Api.Data;
using StockMaster.Api.Models;

namespace StockMaster.Api.Services
{
    public class SimulatedPriceFeedService : IPriceFeedService
    {
        private readonly IServiceScopeFactory _scopeFactory;
        private readonly Random _rnd = new Random();

        public SimulatedPriceFeedService(IServiceScopeFactory scopeFactory)
        {
            _scopeFactory = scopeFactory;
            // start background update
            Task.Run(()=> StartAsync());
        }

        public decimal GetPrice(string symbol)
        {
            using var scope = _scopeFactory.CreateScope();
            var db = scope.ServiceProvider.GetRequiredService<ApplicationDbContext>();
            var asset = db.Assets.FirstOrDefault(a => a.Symbol == symbol);
            return asset?.LastPrice ?? 0m;
        }

        public async Task StartAsync(CancellationToken ct = default)
        {
            while (!ct.IsCancellationRequested)
            {
                using var scope = _scopeFactory.CreateScope();
                var db = scope.ServiceProvider.GetRequiredService<ApplicationDbContext>();
                var assets = await db.Assets.ToListAsync(ct);
                foreach (var a in assets)
                {
                    var change = (decimal)((_rnd.NextDouble() - 0.5) * 0.02); // +/-1%
                    a.LastPrice = Math.Max(0.01m, a.LastPrice * (1 + change));
                    a.LastUpdated = DateTime.UtcNow;
                }
                await db.SaveChangesAsync(ct);
                await Task.Delay(2000, ct); // update every 2s
            }
        }
    }
}
