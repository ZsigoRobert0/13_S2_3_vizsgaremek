namespace StockMaster.Api.Models
{
    public class Position
    {
        public int Id { get; set; }
        public int UserId { get; set; }
        public int AssetId { get; set; }
        public decimal Quantity { get; set; }
        public decimal EntryPrice { get; set; }
        public DateTime OpenedAt { get; set; }
        public DateTime? ClosedAt { get; set; }
        public bool IsLong { get; set; } // buy=true, sell/short=false (simple)
    }
}
