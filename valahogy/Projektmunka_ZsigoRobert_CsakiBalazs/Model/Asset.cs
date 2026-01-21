using System.ComponentModel.DataAnnotations;

namespace StockMaster.Api.Models
{
    public class Asset
    {
        public int Id { get; set; }
        [Required] public string Symbol { get; set; }
        public string Name { get; set; }
        public decimal LastPrice { get; set; }
        public DateTime LastUpdated { get; set; }
    }
}
