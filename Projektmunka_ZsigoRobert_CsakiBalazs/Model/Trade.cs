namespace StockMaster.Api.Models
{
    public class Trade
    {
        public int Id { get; set; }
        public int PositionId { get; set; }
        public DateTime ExecutedAt { get; set; }
        public decimal Price { get; set; }
        public decimal Quantity { get; set; }
        public string Side { get; set; } // "BUY"/"SELL"
    }
}
