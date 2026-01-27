namespace StockMaster.Api.Services
{
    public interface IPriceFeedService
    {
        decimal GetPrice(string symbol);
        Task StartAsync(CancellationToken ct = default);
    }
}
