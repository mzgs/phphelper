<?php

namespace PhpHelper;

class Countries
{
    /**
     * Full country list with flag, iso, iso3
     */
    public static $countries = [
    ["flag" => "🇦🇫", "name" => "Afghanistan", "iso" => "AF", "iso3" => "AFG"],
    ["flag" => "🇦🇱", "name" => "Albania", "iso" => "AL", "iso3" => "ALB"],
    ["flag" => "🇩🇿", "name" => "Algeria", "iso" => "DZ", "iso3" => "DZA"],
    ["flag" => "🇦🇸", "name" => "American Samoa", "iso" => "AS", "iso3" => "ASM"],
    ["flag" => "🇦🇩", "name" => "Andorra", "iso" => "AD", "iso3" => "AND"],
    ["flag" => "🇦🇴", "name" => "Angola", "iso" => "AO", "iso3" => "AGO"],
    ["flag" => "🇦🇮", "name" => "Anguilla", "iso" => "AI", "iso3" => "AIA"],
    ["flag" => "🇦🇬", "name" => "Antigua and Barbuda", "iso" => "AG", "iso3" => "ATG"],
    ["flag" => "🇦🇷", "name" => "Argentina", "iso" => "AR", "iso3" => "ARG"],
    ["flag" => "🇦🇲", "name" => "Armenia", "iso" => "AM", "iso3" => "ARM"],
    ["flag" => "🇦🇼", "name" => "Aruba", "iso" => "AW", "iso3" => "ABW"],
    ["flag" => "🇦🇺", "name" => "Australia", "iso" => "AU", "iso3" => "AUS"],
    ["flag" => "🇦🇹", "name" => "Austria", "iso" => "AT", "iso3" => "AUT"],
    ["flag" => "🇦🇿", "name" => "Azerbaijan", "iso" => "AZ", "iso3" => "AZE"],
    ["flag" => "🇧🇸", "name" => "Bahamas", "iso" => "BS", "iso3" => "BHS"],
    ["flag" => "🇧🇭", "name" => "Bahrain", "iso" => "BH", "iso3" => "BHR"],
    ["flag" => "🇧🇩", "name" => "Bangladesh", "iso" => "BD", "iso3" => "BGD"],
    ["flag" => "🇧🇧", "name" => "Barbados", "iso" => "BB", "iso3" => "BRB"],
    ["flag" => "🇧🇾", "name" => "Belarus", "iso" => "BY", "iso3" => "BLR"],
    ["flag" => "🇧🇪", "name" => "Belgium", "iso" => "BE", "iso3" => "BEL"],
    ["flag" => "🇧🇿", "name" => "Belize", "iso" => "BZ", "iso3" => "BLZ"],
    ["flag" => "🇧🇯", "name" => "Benin", "iso" => "BJ", "iso3" => "BEN"],
    ["flag" => "🇧🇲", "name" => "Bermuda", "iso" => "BM", "iso3" => "BMU"],
    ["flag" => "🇧🇹", "name" => "Bhutan", "iso" => "BT", "iso3" => "BTN"],
    ["flag" => "🇧🇴", "name" => "Bolivia", "iso" => "BO", "iso3" => "BOL"],
    ["flag" => "🇧🇦", "name" => "Bosnia and Herzegovina", "iso" => "BA", "iso3" => "BIH"],
    ["flag" => "🇧🇼", "name" => "Botswana", "iso" => "BW", "iso3" => "BWA"],
    ["flag" => "🇧🇷", "name" => "Brazil", "iso" => "BR", "iso3" => "BRA"],
    ["flag" => "🇻🇬", "name" => "British Virgin Islands", "iso" => "VG", "iso3" => "VGB"],
    ["flag" => "🇧🇳", "name" => "Brunei Darussalam", "iso" => "BN", "iso3" => "BRN"],
    ["flag" => "🇧🇬", "name" => "Bulgaria", "iso" => "BG", "iso3" => "BGR"],
    ["flag" => "🇧🇫", "name" => "Burkina Faso", "iso" => "BF", "iso3" => "BFA"],
    ["flag" => "🇧🇮", "name" => "Burundi", "iso" => "BI", "iso3" => "BDI"],
    ["flag" => "🇰🇭", "name" => "Cambodia", "iso" => "KH", "iso3" => "KHM"],
    ["flag" => "🇨🇲", "name" => "Cameroon", "iso" => "CM", "iso3" => "CMR"],
    ["flag" => "🇨🇦", "name" => "Canada", "iso" => "CA", "iso3" => "CAN"],
    ["flag" => "🇨🇻", "name" => "Cape Verde", "iso" => "CV", "iso3" => "CPV"],
    ["flag" => "🇨🇫", "name" => "Central African Republic", "iso" => "CF", "iso3" => "CAF"],
    ["flag" => "🇹🇩", "name" => "Chad", "iso" => "TD", "iso3" => "TCD"],
    ["flag" => "🇨🇱", "name" => "Chile", "iso" => "CL", "iso3" => "CHL"],
    ["flag" => "🇨🇳", "name" => "China", "iso" => "CN", "iso3" => "CHN"],
    ["flag" => "🇭🇰", "name" => "Hong Kong", "iso" => "HK", "iso3" => "HKG"],
    ["flag" => "🇲🇴", "name" => "Macao", "iso" => "MO", "iso3" => "MAC"],
    ["flag" => "🇨🇴", "name" => "Colombia", "iso" => "CO", "iso3" => "COL"],
    ["flag" => "🇰🇲", "name" => "Comoros", "iso" => "KM", "iso3" => "COM"],
    ["flag" => "🇨🇬", "name" => "Congo", "iso" => "CG", "iso3" => "COG"],
    ["flag" => "🇨🇷", "name" => "Costa Rica", "iso" => "CR", "iso3" => "CRI"],
    ["flag" => "🇨🇮", "name" => "Côte d'Ivoire", "iso" => "CI", "iso3" => "CIV"],
    ["flag" => "🇭🇷", "name" => "Croatia", "iso" => "HR", "iso3" => "HRV"],
    ["flag" => "🇨🇺", "name" => "Cuba", "iso" => "CU", "iso3" => "CUB"],
    ["flag" => "🇨🇾", "name" => "Cyprus", "iso" => "CY", "iso3" => "CYP"],
    ["flag" => "🇨🇿", "name" => "Czech Republic", "iso" => "CZ", "iso3" => "CZE"],
    ["flag" => "🇩🇰", "name" => "Denmark", "iso" => "DK", "iso3" => "DNK"],
    ["flag" => "🇩🇯", "name" => "Djibouti", "iso" => "DJ", "iso3" => "DJI"],
    ["flag" => "🇩🇲", "name" => "Dominica", "iso" => "DM", "iso3" => "DMA"],
    ["flag" => "🇩🇴", "name" => "Dominican Republic", "iso" => "DO", "iso3" => "DOM"],
    ["flag" => "🇪🇨", "name" => "Ecuador", "iso" => "EC", "iso3" => "ECU"],
    ["flag" => "🇪🇬", "name" => "Egypt", "iso" => "EG", "iso3" => "EGY"],
    ["flag" => "🇸🇻", "name" => "El Salvador", "iso" => "SV", "iso3" => "SLV"],
    ["flag" => "🇬🇶", "name" => "Equatorial Guinea", "iso" => "GQ", "iso3" => "GNQ"],
    ["flag" => "🇪🇷", "name" => "Eritrea", "iso" => "ER", "iso3" => "ERI"],
    ["flag" => "🇪🇪", "name" => "Estonia", "iso" => "EE", "iso3" => "EST"],
    ["flag" => "🇪🇹", "name" => "Ethiopia", "iso" => "ET", "iso3" => "ETH"],
    ["flag" => "🇫🇴", "name" => "Faroe Islands", "iso" => "FO", "iso3" => "FRO"],
    ["flag" => "🇫🇯", "name" => "Fiji", "iso" => "FJ", "iso3" => "FJI"],
    ["flag" => "🇫🇮", "name" => "Finland", "iso" => "FI", "iso3" => "FIN"],
    ["flag" => "🇫🇷", "name" => "France", "iso" => "FR", "iso3" => "FRA"],
    ["flag" => "🇬🇫", "name" => "French Guiana", "iso" => "GF", "iso3" => "GUF"],
    ["flag" => "🇵🇫", "name" => "French Polynesia", "iso" => "PF", "iso3" => "PYF"],
    ["flag" => "🇬🇦", "name" => "Gabon", "iso" => "GA", "iso3" => "GAB"],
    ["flag" => "🇬🇲", "name" => "Gambia", "iso" => "GM", "iso3" => "GMB"],
    ["flag" => "🇬🇪", "name" => "Georgia", "iso" => "GE", "iso3" => "GEO"],
    ["flag" => "🇩🇪", "name" => "Germany", "iso" => "DE", "iso3" => "DEU"],
    ["flag" => "🇬🇭", "name" => "Ghana", "iso" => "GH", "iso3" => "GHA"],
    ["flag" => "🇬🇷", "name" => "Greece", "iso" => "GR", "iso3" => "GRC"],
    ["flag" => "🇬🇱", "name" => "Greenland", "iso" => "GL", "iso3" => "GRL"],
    ["flag" => "🇬🇩", "name" => "Grenada", "iso" => "GD", "iso3" => "GRD"],
    ["flag" => "🇬🇵", "name" => "Guadeloupe", "iso" => "GP", "iso3" => "GLP"],
    ["flag" => "🇬🇺", "name" => "Guam", "iso" => "GU", "iso3" => "GUM"],
    ["flag" => "🇬🇹", "name" => "Guatemala", "iso" => "GT", "iso3" => "GTM"],
    ["flag" => "🇬🇼", "name" => "Guinea-Bissau", "iso" => "GW", "iso3" => "GNB"],
    ["flag" => "🇭🇹", "name" => "Haiti", "iso" => "HT", "iso3" => "HTI"],
    ["flag" => "🇭🇳", "name" => "Honduras", "iso" => "HN", "iso3" => "HND"],
    ["flag" => "🇭🇺", "name" => "Hungary", "iso" => "HU", "iso3" => "HUN"],
    ["flag" => "🇮🇸", "name" => "Iceland", "iso" => "IS", "iso3" => "ISL"],
    ["flag" => "🇮🇳", "name" => "India", "iso" => "IN", "iso3" => "IND"],
    ["flag" => "🇮🇩", "name" => "Indonesia", "iso" => "ID", "iso3" => "IDN"],
    ["flag" => "🇮🇷", "name" => "Iran (Islamic Republic of)", "iso" => "IR", "iso3" => "IRN"],
    ["flag" => "🇮🇶", "name" => "Iraq", "iso" => "IQ", "iso3" => "IRQ"],
    ["flag" => "🇮🇪", "name" => "Ireland", "iso" => "IE", "iso3" => "IRL"],
    ["flag" => "🇮🇱", "name" => "Israel", "iso" => "IL", "iso3" => "ISR"],
    ["flag" => "🇮🇹", "name" => "Italy", "iso" => "IT", "iso3" => "ITA"],
    ["flag" => "🇯🇲", "name" => "Jamaica", "iso" => "JM", "iso3" => "JAM"],
    ["flag" => "🇯🇵", "name" => "Japan", "iso" => "JP", "iso3" => "JPN"],
    ["flag" => "🇯🇴", "name" => "Jordan", "iso" => "JO", "iso3" => "JOR"],
    ["flag" => "🇰🇿", "name" => "Kazakhstan", "iso" => "KZ", "iso3" => "KAZ"],
    ["flag" => "🇰🇪", "name" => "Kenya", "iso" => "KE", "iso3" => "KEN"],
    ["flag" => "🇰🇮", "name" => "Kiribati", "iso" => "KI", "iso3" => "KIR"],
    ["flag" => "🇰🇵", "name" => "North Korea", "iso" => "KP", "iso3" => "PRK"],
    ["flag" => "🇰🇷", "name" => "South Korea", "iso" => "KR", "iso3" => "KOR"],
    ["flag" => "🇰🇼", "name" => "Kuwait", "iso" => "KW", "iso3" => "KWT"],
    ["flag" => "🇰🇬", "name" => "Kyrgyzstan", "iso" => "KG", "iso3" => "KGZ"],
    ["flag" => "🇱🇦", "name" => "Lao PDR", "iso" => "LA", "iso3" => "LAO"],
    ["flag" => "🇱🇻", "name" => "Latvia", "iso" => "LV", "iso3" => "LVA"],
    ["flag" => "🇱🇧", "name" => "Lebanon", "iso" => "LB", "iso3" => "LBN"],
    ["flag" => "🇱🇸", "name" => "Lesotho", "iso" => "LS", "iso3" => "LSO"],
    ["flag" => "🇱🇷", "name" => "Liberia", "iso" => "LR", "iso3" => "LBR"],
    ["flag" => "🇱🇾", "name" => "Libya", "iso" => "LY", "iso3" => "LBY"],
    ["flag" => "🇱🇮", "name" => "Liechtenstein", "iso" => "LI", "iso3" => "LIE"],
    ["flag" => "🇱🇹", "name" => "Lithuania", "iso" => "LT", "iso3" => "LTU"],
    ["flag" => "🇱🇺", "name" => "Luxembourg", "iso" => "LU", "iso3" => "LUX"],
    ["flag" => "🇲🇬", "name" => "Madagascar", "iso" => "MG", "iso3" => "MDG"],
    ["flag" => "🇲🇼", "name" => "Malawi", "iso" => "MW", "iso3" => "MWI"],
    ["flag" => "🇲🇾", "name" => "Malaysia", "iso" => "MY", "iso3" => "MYS"],
    ["flag" => "🇲🇻", "name" => "Maldives", "iso" => "MV", "iso3" => "MDV"],
    ["flag" => "🇲🇱", "name" => "Mali", "iso" => "ML", "iso3" => "MLI"],
    ["flag" => "🇲🇹", "name" => "Malta", "iso" => "MT", "iso3" => "MLT"],
    ["flag" => "🇲🇭", "name" => "Marshall Islands", "iso" => "MH", "iso3" => "MHL"],
    ["flag" => "🇲🇶", "name" => "Martinique", "iso" => "MQ", "iso3" => "MTQ"],
    ["flag" => "🇲🇷", "name" => "Mauritania", "iso" => "MR", "iso3" => "MRT"],
    ["flag" => "🇲🇺", "name" => "Mauritius", "iso" => "MU", "iso3" => "MUS"],
    ["flag" => "🇲🇽", "name" => "Mexico", "iso" => "MX", "iso3" => "MEX"],
    ["flag" => "🇫🇲", "name" => "Micronesia, Federated States of", "iso" => "FM", "iso3" => "FSM"],
    ["flag" => "🇲🇩", "name" => "Moldova", "iso" => "MD", "iso3" => "MDA"],
    ["flag" => "🇲🇨", "name" => "Monaco", "iso" => "MC", "iso3" => "MCO"],
    ["flag" => "🇲🇳", "name" => "Mongolia", "iso" => "MN", "iso3" => "MNG"],
    ["flag" => "🇲🇪", "name" => "Montenegro", "iso" => "ME", "iso3" => "MNE"],
    ["flag" => "🇲🇸", "name" => "Montserrat", "iso" => "MS", "iso3" => "MSR"],
    ["flag" => "🇲🇦", "name" => "Morocco", "iso" => "MA", "iso3" => "MAR"],
    ["flag" => "🇲🇿", "name" => "Mozambique", "iso" => "MZ", "iso3" => "MOZ"],
    ["flag" => "🇲🇲", "name" => "Myanmar", "iso" => "MM", "iso3" => "MMR"],
    ["flag" => "🇳🇦", "name" => "Namibia", "iso" => "NA", "iso3" => "NAM"],
    ["flag" => "🇳🇷", "name" => "Nauru", "iso" => "NR", "iso3" => "NRU"],
    ["flag" => "🇳🇵", "name" => "Nepal", "iso" => "NP", "iso3" => "NPL"],
    ["flag" => "🇳🇱", "name" => "Netherlands", "iso" => "NL", "iso3" => "NLD"],
    ["flag" => "🇳🇿", "name" => "New Zealand", "iso" => "NZ", "iso3" => "NZL"],
    ["flag" => "🇳🇮", "name" => "Nicaragua", "iso" => "NI", "iso3" => "NIC"],
    ["flag" => "🇳🇪", "name" => "Niger", "iso" => "NE", "iso3" => "NER"],
    ["flag" => "🇳🇬", "name" => "Nigeria", "iso" => "NG", "iso3" => "NGA"],
    ["flag" => "🇳🇴", "name" => "Norway", "iso" => "NO", "iso3" => "NOR"],
    ["flag" => "🇴🇲", "name" => "Oman", "iso" => "OM", "iso3" => "OMN"],
    ["flag" => "🇵🇰", "name" => "Pakistan", "iso" => "PK", "iso3" => "PAK"],
    ["flag" => "🇵🇼", "name" => "Palau", "iso" => "PW", "iso3" => "PLW"],
    ["flag" => "🇵🇸", "name" => "Palestinian Territory", "iso" => "PS", "iso3" => "PSE"],
    ["flag" => "🇵🇦", "name" => "Panama", "iso" => "PA", "iso3" => "PAN"],
    ["flag" => "🇵🇬", "name" => "Papua New Guinea", "iso" => "PG", "iso3" => "PNG"],
    ["flag" => "🇵🇾", "name" => "Paraguay", "iso" => "PY", "iso3" => "PRY"],
    ["flag" => "🇵🇪", "name" => "Peru", "iso" => "PE", "iso3" => "PER"],
    ["flag" => "🇵🇭", "name" => "Philippines", "iso" => "PH", "iso3" => "PHL"],
    ["flag" => "🇵🇱", "name" => "Poland", "iso" => "PL", "iso3" => "POL"],
    ["flag" => "🇵🇹", "name" => "Portugal", "iso" => "PT", "iso3" => "PRT"],
    ["flag" => "🇵🇷", "name" => "Puerto Rico", "iso" => "PR", "iso3" => "PRI"],
    ["flag" => "🇶🇦", "name" => "Qatar", "iso" => "QA", "iso3" => "QAT"],
    ["flag" => "🇷🇴", "name" => "Romania", "iso" => "RO", "iso3" => "ROU"],
    ["flag" => "🇷🇺", "name" => "Russian Federation", "iso" => "RU", "iso3" => "RUS"],
    ["flag" => "🇷🇼", "name" => "Rwanda", "iso" => "RW", "iso3" => "RWA"],
    ["flag" => "🇰🇳", "name" => "Saint Kitts and Nevis", "iso" => "KN", "iso3" => "KNA"],
    ["flag" => "🇱🇨", "name" => "Saint Lucia", "iso" => "LC", "iso3" => "LCA"],
    ["flag" => "🇻🇨", "name" => "Saint Vincent and Grenadines", "iso" => "VC", "iso3" => "VCT"],
    ["flag" => "🇼🇸", "name" => "Samoa", "iso" => "WS", "iso3" => "WSM"],
    ["flag" => "🇸🇲", "name" => "San Marino", "iso" => "SM", "iso3" => "SMR"],
    ["flag" => "🇸🇹", "name" => "Sao Tome and Principe", "iso" => "ST", "iso3" => "STP"],
    ["flag" => "🇸🇦", "name" => "Saudi Arabia", "iso" => "SA", "iso3" => "SAU"],
    ["flag" => "🇸🇳", "name" => "Senegal", "iso" => "SN", "iso3" => "SEN"],
    ["flag" => "🇷🇸", "name" => "Serbia", "iso" => "RS", "iso3" => "SRB"],
    ["flag" => "🇸🇨", "name" => "Seychelles", "iso" => "SC", "iso3" => "SYC"],
    ["flag" => "🇸🇱", "name" => "Sierra Leone", "iso" => "SL", "iso3" => "SLE"],
    ["flag" => "🇸🇬", "name" => "Singapore", "iso" => "SG", "iso3" => "SGP"],
    ["flag" => "🇸🇰", "name" => "Slovakia", "iso" => "SK", "iso3" => "SVK"],
    ["flag" => "🇸🇮", "name" => "Slovenia", "iso" => "SI", "iso3" => "SVN"],
    ["flag" => "🇸🇧", "name" => "Solomon Islands", "iso" => "SB", "iso3" => "SLB"],
    ["flag" => "🇸🇴", "name" => "Somalia", "iso" => "SO", "iso3" => "SOM"],
    ["flag" => "🇿🇦", "name" => "South Africa", "iso" => "ZA", "iso3" => "ZAF"],
    ["flag" => "🇪🇸", "name" => "Spain", "iso" => "ES", "iso3" => "ESP"],
    ["flag" => "🇱🇰", "name" => "Sri Lanka", "iso" => "LK", "iso3" => "LKA"],
    ["flag" => "🇸🇩", "name" => "Sudan", "iso" => "SD", "iso3" => "SDN"],
    ["flag" => "🇸🇷", "name" => "Suriname", "iso" => "SR", "iso3" => "SUR"],
    ["flag" => "🇸🇪", "name" => "Sweden", "iso" => "SE", "iso3" => "SWE"],
    ["flag" => "🇨🇭", "name" => "Switzerland", "iso" => "CH", "iso3" => "CHE"],
    ["flag" => "🇸🇾", "name" => "Syrian", "iso" => "SY", "iso3" => "SYR"],
    ["flag" => "🇹🇼", "name" => "Taiwan", "iso" => "TW", "iso3" => "TWN"],
    ["flag" => "🇹🇯", "name" => "Tajikistan", "iso" => "TJ", "iso3" => "TJK"],
    ["flag" => "🇹🇿", "name" => "Tanzania", "iso" => "TZ", "iso3" => "TZA"],
    ["flag" => "🇹🇭", "name" => "Thailand", "iso" => "TH", "iso3" => "THA"],
    ["flag" => "🇹🇬", "name" => "Togo", "iso" => "TG", "iso3" => "TGO"],
    ["flag" => "🇹🇴", "name" => "Tonga", "iso" => "TO", "iso3" => "TON"],
    ["flag" => "🇹🇹", "name" => "Trinidad and Tobago", "iso" => "TT", "iso3" => "TTO"],
    ["flag" => "🇹🇳", "name" => "Tunisia", "iso" => "TN", "iso3" => "TUN"],
    ["flag" => "🇹🇷", "name" => "Turkey", "iso" => "TR", "iso3" => "TUR"],
    ["flag" => "🇹🇲", "name" => "Turkmenistan", "iso" => "TM", "iso3" => "TKM"],
    ["flag" => "🇹🇻", "name" => "Tuvalu", "iso" => "TV", "iso3" => "TUV"],
    ["flag" => "🇺🇬", "name" => "Uganda", "iso" => "UG", "iso3" => "UGA"],
    ["flag" => "🇺🇦", "name" => "Ukraine", "iso" => "UA", "iso3" => "UKR"],
    ["flag" => "🇦🇪", "name" => "United Arab Emirates", "iso" => "AE", "iso3" => "ARE"],
    ["flag" => "🇬🇧", "name" => "United Kingdom", "iso" => "GB", "iso3" => "GBR"],
    ["flag" => "🇺🇸", "name" => "United States", "iso" => "US", "iso3" => "USA"],
    ["flag" => "🇺🇾", "name" => "Uruguay", "iso" => "UY", "iso3" => "URY"],
    ["flag" => "🇺🇿", "name" => "Uzbekistan", "iso" => "UZ", "iso3" => "UZB"],
    ["flag" => "🇻🇺", "name" => "Vanuatu", "iso" => "VU", "iso3" => "VUT"],
    ["flag" => "🇻🇪", "name" => "Venezuela", "iso" => "VE", "iso3" => "VEN"],
    ["flag" => "🇻🇳", "name" => "Viet Nam", "iso" => "VN", "iso3" => "VNM"],
    ["flag" => "🇾🇪", "name" => "Yemen", "iso" => "YE", "iso3" => "YEM"],
    ["flag" => "🇿🇲", "name" => "Zambia", "iso" => "ZM", "iso3" => "ZMB"],
    ["flag" => "🇿🇼", "name" => "Zimbabwe", "iso" => "ZW", "iso3" => "ZWE"],
    ["flag" => "🇽🇰", "name" => "Kosovo", "iso" => "XK", "iso3" => "XKS"],
];

 
  
     static function getAll(): array
    {
        return self::$countries;
    }

    // 2 or 3 letter country code
      static function getByCode(string $code): ?array
    {
        foreach (self::$countries as $country) {
            if (strtoupper($country['iso']) === strtoupper($code) || strtoupper($country['iso3']) === strtoupper($code)) {
                return $country;
            }
        }
        return null;
    }

    // country name
      static function getByName(string $name): ?array
    {
        foreach (self::$countries as $country) {
            if (strtolower($country['name']) === strtolower($name)) {
                return $country;
            }
        }
        return null;
    }

    // country flag
      static function nameWithFlag(string $code): ?string
    {
        $country = self::getByCode($code);
        if ($country) {
            return $country['flag'] . ' ' . $country['name'];
        }
        return null;
    }


}
