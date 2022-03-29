<?php
namespace Make\Database;

use Corelib\Func;

class Pdosql {

    static private $DB_HOST = DB_HOST;
    static private $DB_NAME = DB_NAME;
    static private $DB_USER = DB_USER;
    static private $DB_PWD = DB_PWD;
    static private $DB_PREFIX = DB_PREFIX;
    static private $CONN;
    private $ROW;
    private $REC_COUNT;
    private $pdo;
    private $stmt;

    //pdo 연결 초기화
    public function __construct()
    {
        try {

            switch (DB_ENGINE) {
                default :
                    $this->pdo = new \PDO(
                        'mysql:host='.self::$DB_HOST.';dbname='.self::$DB_NAME, self::$DB_USER, self::$DB_PWD,
                        array(
                            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                        )
                    );
            }

        }
        catch (\PDOException $e) {
            Func::core_err(ERR_MSG_3.'<br />'.$e->getMessage());
        }

        $this->specialchars = DB_SPECIALCHARS;
        $this->nl2br = DB_NL2BR;
    }

    //pdo 연결 종료
    public function close()
    {
        $this->pdo = null;
    }

    //테이블 명칭 조합 후 반환
    public function table_exists($tblName)
    {
        $this->query(
            "
            SELECT COUNT(*) booleans
            FROM Information_schema.tables
            WHERE table_schema = '".self::$DB_NAME."'
            AND table_name = '".$this->table($tblName)."'
            "
        );

        return $this->fetch('booleans');
    }

    //테이블 명칭 조합 후 반환
    public function table($tblName)
    {
        $expl = explode(':', $tblName);
        $tbl = '';

        //모듈 Table인 경우
        if (count($expl) > 1) {
            if ($expl[1]) {
                $tbl = self::$DB_PREFIX.$expl[0].'_'.$expl[1];
            } else {
                $tbl = self::$DB_PREFIX.$expl[0];
            }
        }

        //그 외 기본 Table
        else {
            $tbl = self::$DB_PREFIX.$tblName;
        }
        return $tbl;
    }

    //Query
    public function query($query, $param = [])
    {


        try {

            $qryString = $query;

            $this->stmt = $this->pdo->prepare($query);

            if (is_array($param)) {
                for ($i=1; $i <= count($param); $i++) {
                    $this->stmt->bindParam(':col'.$i, $param[$i-1]);
                    $qryString = str_replace(':col'.$i, $param[$i-1], $qryString);
                }
            }

            $this->stmt->execute();
            $this->REC_COUNT = $this->stmt->rowCount();

            if ( strpos(strtolower($query),'select') !== false && ( strpos(strtolower($query),'insert') === false && strpos(strtolower($query),'update') === false ) ) {
                $this->ROW = $this->stmt->fetch(\PDO::FETCH_ASSOC);
            }
            $this->ROW_NUM = 0;

            return $qryString;

        }
        catch (\PDOException $e) {
            Func::core_err(ERR_MSG_5.'<br />'.$e->getMessage());
        }
    }

    //레코드의 갯수를 구함
    public function getcount()
    {
        return $this->REC_COUNT;
    }

    //첫번째 레코드에 위치 시킴
    public function firstRec()
    {
        $this->ROW = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, 0);
    }

    //마지막 레코드에 위치 시킴
    public function lastRec()
    {
        $this->ROW = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_LAST);
    }

    //다음 레코드에 위치 시킴
    public function nextRec()
    {
        $this->ROW_NUM = $this->ROW_NUM + 1;

        if ($this->ROW_NUM < $this->REC_COUNT) {
            $this->ROW = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_REL, $this->ROW_NUM);
            return true;

        } else {
            return false;
        }
    }

    //이전 레코드에 위치 시킴
    public function prevRec()
    {
        $this->ROW_NUM = $this->ROW_NUM - 1;
        if ($this->ROW_NUM >= 0) {
            $this->ROW = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_REL, $this->ROW_NUM);
            return true;

        } else {
            return false;
        }
    }

    //레코드의 특정 필드 값을 가져옴
    public function fetch($fieldName)
    {
        if (isset($this->ROW[$fieldName])) {
            $this->ROW_RE = stripslashes($this->ROW[$fieldName]);
            if ($this->specialchars == 1) {
                $this->ROW_RE = htmlspecialchars($this->ROW_RE);
            }
            if ($this->nl2br == 1) {
                $this->ROW_RE = nl2br($this->ROW_RE);
            }
            $match_charsets = "\x{0410}-\x{044F}\x{0500}-\x{052F}\x{0400}-\x{04FF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{31F0}-\x{31FF}\x{2E80}-\x{2EFF}\x{31C0}-\x{31EF}\x{3200}-\x{32FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FBF}\x{F900}-\x{FAFF}\x{20000}-\x{2A6DF}\x{2F800}-\x{2FA1F}\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}\x20-\x7e";
            $match_charsets .= "\x{FF01}\x{FF07}\x{FF0C}\x{FF0E}\x{FF0F}\x{FF1A}\x{FF1B}\x{FF1F}\x{FF3E}\x{FF3F}\x{FF40}\x{FF5C}\x{FFE3}\x{3001}\x{3002}\x{B7}\x{2025}\x{2026}\x{A8}\x{3003}\x{AD}\x{2015}\x{2225}\x{FF3C}\x{223C}\x{B4}\x{FF5E}\x{2C7}\x{2D8}\x{2DD}\x{2DA}\x{2D9}\x{B8}\x{2DB}\x{A1}\x{BF}\x{2D0}";
            $match_charsets .= "\x{FF03}\x{FF06}\x{FF0A}\x{FF20}\x{A7}\x{203B}\x{2606}\x{2605}\x{25CB}\x{25CF}\x{25CE}\x{25C7}\x{25C6}\x{25A1}\x{25A0}\x{25B3}\x{25B2}\x{25BD}\x{25BC}\x{2192}\x{2190}\x{2191}\x{2193}\x{2194}\x{3013}\x{25C1}\x{25C0}\x{25B7}\x{25B6}\x{2664}\x{2660}\x{2661}\x{2665}\x{2667}\x{2663}\x{2299}\x{25C8}\x{25A3}\x{25D0}\x{25D1}\x{2592}\x{25A4}\x{25A5}\x{25A8}\x{25A7}\x{25A6}\x{25A9}\x{2668}\x{260F}\x{260E}\x{261C}\x{261E}\x{B6}\x{2020}\x{2021}\x{2195}\x{2197}\x{2199}\x{2196}\x{2198}\x{266D}\x{2669}\x{266A}\x{266C}\x{327F}\x{321C}\x{2116}\x{33C7}\x{2122}\x{33C2}\x{33D8}\x{2121} ??\x{AA}\x{BA}";
            $match_charsets .= "\x{FF03}\x{FF06}\x{FF0A}\x{FF20}\x{A7}\x{203B}\x{2606}\x{2605}\x{25CB}\x{25CF}\x{25CE}\x{25C7}\x{25C6}\x{25A1}\x{25A0}\x{25B3}\x{25B2}\x{25BD}\x{25BC}\x{2192}\x{2190}\x{2191}\x{2193}\x{2194}\x{3013}\x{25C1}\x{25C0}\x{25B7}\x{25B6}\x{2664}\x{2660}\x{2661}\x{2665}\x{2667}\x{2663}\x{2299}\x{25C8}\x{25A3}\x{25D0}\x{25D1}\x{2592}\x{25A4}\x{25A5}\x{25A8}\x{25A7}\x{25A6}\x{25A9}\x{2668}\x{260F}\x{260E}\x{261C}\x{261E}\x{B6}\x{2020}\x{2021}\x{2195}\x{2197}\x{2199}\x{2196}\x{2198}\x{266D}\x{2669}\x{266A}\x{266C}\x{327F}\x{321C}\x{2116}\x{33C7}\x{2122}\x{33C2}\x{33D8}\x{2121} ??\x{AA}\x{BA}";
            $match_charsets .= "\x{C6}\x{D0}\x{126}\x{132}\x{13F}\x{141}\x{D8}\x{152}\x{DE}\x{166}\x{14A}\x{E6}\x{111}\x{F0}\x{141}\x{D8}\x{133}\x{138}\x{140}\x{142}\x{F8}\x{153}\x{DF}\x{FE}\x{167}\x{14B}\x{149}\x{FF02}\x{FF08}\x{FF09}\x{FF3B}\x{FF3D}\x{FF5B}\x{FF5D}\x{2018}\x{2019}\x{201C}\x{201D}\x{3014}\x{3015}\x{3008}\x{3009}\x{300A}\x{300B}\x{300C}\x{300D}\x{300E}\x{300F}\x{3010}\x{3011}\x{FF0B}\x{FF0D}\x{FF1C}\x{FF1D}\x{FF1E}\x{B1}\x{D7}\x{F7}\x{2260}\x{2264}\x{2265}\x{221E}\x{2234}\x{2642}\x{2640}\x{2220}\x{22A5}\x{2312}\x{2202}\x{2207}\x{2261}\x{2252}\x{226A}\x{226B}\x{221A}\x{223D}\x{221D}\x{2235}\x{222B}\x{222C}\x{2208}\x{220B}\x{2286}\x{2287}\x{2282}\x{2283}\x{222A}\x{2229}\x{2227}\x{2228}\x{FFE2}\x{21D2}\x{21D4}\x{2200}\x{2203}\x{222E}\x{2211}\x{220F}";
            $match_charsets .= "\x{3041}\x{3042}\x{3043}\x{3044}\x{3045}\x{3046}\x{3047}\x{3048}\x{3049}\x{304A}\x{304B}\x{304C}\x{304D}\x{304E}\x{3046}\x{3050}\x{3051}\x{3052}\x{3053}\x{3054}\x{3055}\x{3056}\x{3057}\x{3058}\x{3059}\x{305A}\x{305B}\x{305C}\x{305D}\x{305E}\x{305F}\x{3060}\x{3061}\x{3062}\x{3063}\x{3064}\x{3065}\x{3066}\x{305E}\x{305F}\x{3060}\x{3061}\x{3062}\x{3063}\x{3064}\x{3065}\x{3066}\x{3067}\x{3068}\x{3069}\x{306A}\x{306B}\x{306C}\x{306D}\x{306E}\x{306F}\x{3070}\x{3071}\x{3072}\x{3073}\x{3074}\x{3075}\x{3076}\x{3077}\x{3078}\x{3079}\x{307A}\x{307B}\x{307C}\x{307D}\x{307E}\x{307F}\x{3080}\x{3081}\x{3082}\x{3083}\x{3084}\x{3085}\x{3086}\x{3087}\x{3088}\x{3089}\x{308A}\x{308B}\x{308C}\x{308D}\x{308E}\x{308F}\x{3090}\x{3091}\x{3092}\x{3093}";
            $match_charsets .= "\x{FF04}\x{FF05}\x{FFE6}\x{FF26}\x{2032}\x{2033}\x{2103}\x{212B}\x{FFE0}\x{FFE1}\x{FFE5}\x{A4}\x{2109}\x{2030} ??\x{3395}\x{3396}\x{3397}\x{2113}\x{3398}\x{33C4}\x{33A3}\x{33A4}\x{33A5}\x{33A6}\x{3399}\x{339A}\x{339B}\x{339C}\x{339D}\x{339E}\x{339F}\x{33A0}\x{33A1}\x{3399}\x{33CA}\x{338D}\x{338E}\x{338F}\x{33CF}\x{3388}\x{3389}\x{33C8}\x{33A7}\x{33A8}\x{33B0}\x{33B1}\x{33B2}\x{33B3}\x{33B4}\x{33B5}\x{33B6}\x{33B7}\x{33B8}\x{33B9}\x{3380}\x{3381}\x{3382}\x{3383}\x{3384}\x{33BA}\x{33BB}\x{33BC}\x{33BD}\x{33BE}\x{33BF}\x{3390}\x{3391}\x{3392}\x{3393}\x{3394}\x{2126}\x{33C0}\x{33C1}\x{338A}\x{338B}\x{338C}\x{33D6}\x{33C5}\x{33AD}\x{33AE}\x{33AF}\x{33DB}\x{33A9}\x{33AA}\x{33AB}\x{33AC}\x{33DD}\x{33D0}\x{33D3}\x{33C3}\x{33C9}\x{33DC}\x{33C6}";
            $match_charsets .= "\x{2500}\x{2502}\x{250C}\x{2510}\x{2518}\x{2514}\x{251C}\x{252C}\x{2524}\x{2534}\x{253C}\x{2501}\x{2503}\x{250F}\x{2513}\x{251B}\x{2517}\x{2523}\x{2533}\x{252B}\x{253B}\x{254B}\x{2520}\x{252F}\x{2528}\x{2537}\x{253F}\x{251D}\x{2530}\x{2525}\x{2538}\x{2542}\x{2512}\x{2511}\x{251A}\x{2519}\x{2516}\x{2515}\x{250E}\x{250D}\x{251E}\x{251F}\x{2521}\x{2522}\x{2526}\x{2527}\x{2529}\x{252A}\x{252D}\x{252E}\x{2531}\x{2532}\x{2535}\x{2536}\x{2539}\x{253A}\x{253D}\x{253E}\x{2540}\x{2541}\x{2543}\x{2544}\x{2545}\x{2546}\x{2547}\x{2548}\x{2549}\x{254A}";
            $match_charsets .= "\x{30A1}\x{30A2}\x{30A3}\x{30A4}\x{30A5}\x{30A6}\x{30A7}\x{30A8}\x{30A9}\x{30AA}\x{30AB}\x{30AC}\x{30AD}\x{30AE}\x{30AF}\x{30B0}\x{30B1}\x{30B2}\x{30B3}\x{30B4}\x{30B5}\x{30B6}\x{30B7}\x{30B8}\x{30B9}\x{30BA}\x{30BB}\x{30BC}\x{30BD}\x{30BE}\x{30BF}\x{30C0}\x{30C1}\x{30C2}\x{30C3}\x{30C4}\x{30C5}\x{30C6}\x{30C7}\x{30C8}\x{30C9}\x{30CA}\x{30CB}\x{30CC}\x{30CD}\x{30CE}\x{30CF}\x{30D0}\x{30D1}\x{30D2}\x{30D3}\x{30D4}\x{30D5}\x{30D6}\x{30D7}\x{30D8}\x{30D9}\x{30DA}\x{30DB}\x{30DC}\x{30DD}\x{30DE}\x{30DF}\x{30E0}\x{30E1}\x{30E2}\x{30E3}\x{30E4}\x{30E5}\x{30E6}\x{30E7}\x{30E8}\x{30E9}\x{30EA}\x{30EB}\x{30EC}\x{30ED}\x{30EE}\x{30EF}\x{30F0}\x{30F1}\x{30F2}\x{30F3}\x{30F4}\x{30F5}\x{30F6}";
            $match_charsets .= "\x{3260}\x{3261}\x{3262}\x{3263}\x{326D}\x{3265}\x{3266}\x{3267}\x{3268}\x{3269}\x{326A}\x{326B}\x{326C}\x{326D}\x{326E}\x{326F}\x{3270}\x{3271}\x{3272}\x{3273}\x{3274}\x{3275}\x{3276}\x{3277}\x{3278}\x{3279}\x{327A}\x{327B}\x{3200}\x{3201}\x{3202}\x{3203}\x{3204}\x{3205}\x{3206}\x{3207}\x{3208}\x{3209}\x{320A}\x{320B}\x{320C}\x{320D}\x{320E}\x{320F}\x{3210}\x{3211}\x{3212}\x{3213}\x{3214}\x{3215}\x{3216}\x{3217}\x{3218}\x{3219}\x{321A}\x{321B}";
            $match_charsets .= "\x{410}\x{411}\x{412}\x{413}\x{414}\x{415}\x{401}\x{416}\x{417}\x{418}\x{419}\x{41A}\x{41B}\x{41C}\x{41D}\x{41E}\x{41F}\x{420}\x{421}\x{422}\x{423}\x{424}\x{425}\x{426}\x{427}\x{428}\x{429}\x{42A}\x{42B}\x{42C}\x{42D}\x{42E}\x{42F}\x{430}\x{431}\x{432}\x{433}\x{434}\x{435}\x{451}\x{436}\x{437}\x{438}\x{439}\x{43A}\x{43B}\x{43C}\x{43D}\x{43E}\x{43F}\x{440}\x{441}\x{442}\x{444}\x{445}\x{446}\x{447}\x{448}\x{449}\x{44A}\x{44B}\x{44B}\x{44C}\x{44D}\x{44E}\x{44F}";
            $match_charsets .= "\x{24D0}\x{24D1}\x{24D2}\x{24D3}\x{24D4}\x{24D5}\x{24D6}\x{24D7}\x{24D8}\x{24D9}\x{24DA}\x{24DB}\x{24DC}\x{24DD}\x{24DE}\x{24D6}\x{24E0}\x{24E1}\x{24E2}\x{24E3}\x{24E4}\x{24E5}\x{24E6}\x{24E7}\x{24E8}\x{24E9}\x{2460}\x{2461}\x{2462}\x{2463}\x{2464}\x{2465}\x{2466}\x{2467}\x{2468}\x{2469}\x{246A}\x{246B}\x{246C}\x{246D}\x{246E}\x{249C}\x{249D}\x{249E}\x{249F}\x{24A0}\x{24A1}\x{24A2}\x{24A3}\x{24A4}\x{24A5}\x{24A6}\x{24A7}\x{24A8}\x{24A9}\x{24AA}\x{24AB}\x{24AC}\x{24AD}\x{24AE}\x{24AF}\x{24B0}\x{24B1}\x{24B2}\x{24B3}\x{24B4}\x{24B5}\x{2474}\x{2475}\x{2476}\x{2477}\x{2478}\x{2479}\x{247A}\x{247B}\x{247C}\x{247D}\x{247E}\x{247F}\x{2480}\x{2481}\x{2482}";
            $match_charsets .= "\x{FF10}\x{FF11}\x{FF12}\x{FF13}\x{FF14}\x{FF15}\x{FF16}\x{FF17}\x{FF18}\x{FF19}\x{2170}\x{2171}\x{2172}\x{2173}\x{2174}\x{2175}\x{2176}\x{2177}\x{2178}\x{2179}\x{2160}\x{2161}\x{2162}\x{2163}\x{2164}\x{2165}\x{2166}\x{2167}\x{2168}\x{2169}";
            $match_charsets .= "\x{BD}\x{2153}\x{2154}\x{BC}\x{BE}\x{215B}\x{215C}\x{215D}\x{215E}\x{B9}\x{B2}\x{B3}\x{2074}\x{207F}\x{2081}\x{2082}\x{2083}\x{2084}";
            $match_charsets .= "\x{3131}\x{3132}\x{3133}\x{3134}\x{3135}\x{3136}\x{3137}\x{3138}\x{3139}\x{313A}\x{313B}\x{313C}\x{313D}\x{313E}\x{313F}\x{3140}\x{3141}\x{3142}\x{3143}\x{3144}\x{3145}\x{3146}\x{3147}\x{3148}\x{3149}\x{314A}\x{314B}\x{314C}\x{314D}\x{314E}\x{314F}\x{3150}\x{3151}\x{3152}\x{3153}\x{3154}\x{3155}\x{3156}\x{3157}\x{3158}\x{3159}\x{315A}\x{315B}\x{315C}\x{315D}\x{315E}\x{315F}\x{3160}\x{3161}\x{3162}\x{3163}";
            $match_charsets .= "\x{3165}\x{3166}\x{3167}\x{3168}\x{3169}\x{316A}\x{316B}\x{316C}\x{316D}\x{316E}\x{316F}\x{3170}\x{3171}\x{3172}\x{3173}\x{3174}\x{3175}\x{3176}\x{3177}\x{3178}\x{3179}\x{317A}\x{317B}\x{317C}\x{317D}\x{317E}\x{317F}\x{3180}\x{3181}\x{3182}\x{3183}\x{3184}\x{3185}\x{3186}\x{3187}\x{3188}\x{3189}\x{318A}\x{318B}\x{318C}\x{318D}\x{318E}";
            $match_charsets .= "\x{FF21}\x{FF22}\x{FF23}\x{FF24}\x{FF25}\x{FF26}\x{FF27}\x{FF28}\x{FF29}\x{FF2A}\x{FF2B}\x{FF2C}\x{FF2D}\x{FF2E}\x{FF2F}\x{FF30}\x{FF31}\x{FF32}\x{FF33}\x{FF34}\x{FF35}\x{FF36}\x{FF37}\x{FF38}\x{FF39}\x{FF3A}\x{FF41}\x{FF42}\x{FF43}\x{FF44}\x{FF45}\x{FF46}\x{FF47}\x{FF48}\x{FF49}\x{FF4A}\x{FF4B}\x{FF4C}\x{FF4D}\x{FF4E}\x{FF4F}\x{FF50}\x{FF51}\x{FF52}\x{FF53}\x{FF54}\x{FF55}\x{FF56}\x{FF57}\x{FF58}\x{FF59}\x{FF5A}";
            $match_charsets .= "\x{391}\x{392}\x{393}\x{394}\x{395}\x{396}\x{397}\x{398}\x{399}\x{39A}\x{39B}\x{39C}\x{39D}\x{39E}\x{39F}\x{3A0}\x{3A1}\x{3A3}\x{3A4}\x{3A5}\x{3A6}\x{3A7}\x{3A8}\x{3A9}\x{3B1}\x{3B2}\x{3B3}\x{3B4}\x{3B5}\x{3B6}\x{3B7}\x{3B8}\x{3B9}\x{3BA}\x{3BB}\x{3BC}\x{3BD}\x{3BE}\x{3BF}\x{3C0}\x{3C1}\x{3C3}\x{3C4}\x{3C5}\x{3C6}\x{3C7}\x{3C8}\x{3C9}";
            return preg_replace("/[^\r\n|\r|\n".$match_charsets."]/u", '', stripslashes($this->ROW_RE));

        } else {
            return '';
        }
    }

    //레코드의 모든 필드 값을 배열로 가져옴
    public function fetchs()
    {
        $array = array();

        if (!$this->ROW) {
            return false;
        }
        foreach ($this->ROW as $key => $value) {
            $array[$key] = stripslashes($this->fetch($key));
        }
        return $array;
    }

    //여분필드 설명 처리
    public function etcfd_exp($exp)
    {
        $ex = explode('|', $exp);

        for ($i=0; $i < 10; $i++) {
            if (!isset($ex[$i])) {
                $ex[$i] = '';
            }
        }
        return implode('|', $ex);
    }
}
