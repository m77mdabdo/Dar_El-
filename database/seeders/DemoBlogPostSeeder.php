<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Services\DemoImageManifest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 50 bilingual blog posts built from a small set of real, hand-written
 * paragraph templates (not Lorem Ipsum) applied across garment types,
 * seasons, and fabrics — the same templated-composition technique used by
 * DemoProductSeeder, chosen so 50 posts stay genuinely readable and on
 * topic without requiring 50 fully independent essays.
 */
class DemoBlogPostSeeder extends Seeder
{
    protected const AUTHORS = ['Layla Hassan', 'Fatima Al-Sayed', 'Mona Abdel Rahman', 'Nourhan Khaled'];

    public function run(): void
    {
        $manifest = DemoImageManifest::load();
        $images = $manifest['blog'] ?? [];

        if (empty($images)) {
            $this->command?->warn('Skipping blog posts — no imported images available yet. Run php artisan demo:import first.');

            return;
        }

        $posts = $this->definitions();

        foreach ($posts as $i => $post) {
            $image = $images[$i % count($images)];

            if (! Storage::disk('public')->exists($image)) {
                continue;
            }

            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'title_ar' => $post['title_ar'],
                    'title_en' => $post['title_en'],
                    'excerpt_ar' => $post['excerpt_ar'],
                    'excerpt_en' => $post['excerpt_en'],
                    'body_ar' => $post['body_ar'],
                    'body_en' => $post['body_en'],
                    'cover_image' => $image,
                    'author_name' => self::AUTHORS[$i % count(self::AUTHORS)],
                    'category' => $post['category'],
                    'meta_title_ar' => $post['title_ar'].' — مدونة دار الجميلة',
                    'meta_title_en' => $post['title_en'].' — Dar El-Jamila Blog',
                    'meta_description_ar' => $post['excerpt_ar'],
                    'meta_description_en' => $post['excerpt_en'],
                    'is_published' => true,
                    'published_at' => now()->subDays(count($posts) - $i),
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function definitions(): array
    {
        $posts = [];

        $garments = [
            ['en' => 'Abaya', 'ar' => 'العباية'],
            ['en' => 'Kaftan', 'ar' => 'القفطان'],
            ['en' => 'Evening Dress', 'ar' => 'فستان السهرة'],
            ['en' => 'Hijab', 'ar' => 'الحجاب'],
            ['en' => 'Scarf', 'ar' => 'الإيشارب'],
            ['en' => 'Jalabiya', 'ar' => 'الجلابية'],
            ['en' => 'Prayer Set', 'ar' => 'طقم الصلاة'],
            ['en' => 'Handbag', 'ar' => 'الحقيبة'],
        ];

        foreach ($garments as $garment) {
            $posts[] = $this->chooseTheRight($garment);
        }

        foreach ($garments as $garment) {
            $posts[] = $this->stylingForOccasions($garment);
        }

        $seasons = [
            ['en' => 'Spring', 'ar' => 'الربيع'], ['en' => 'Summer', 'ar' => 'الصيف'],
            ['en' => 'Autumn', 'ar' => 'الخريف'], ['en' => 'Winter', 'ar' => 'الشتاء'],
        ];

        foreach ($seasons as $season) {
            $posts[] = $this->seasonalAdvice($season);
        }

        $fabrics = [
            ['en' => 'Embroidered', 'ar' => 'المطرزة'], ['en' => 'Silk', 'ar' => 'الحرير'], ['en' => 'Wool', 'ar' => 'الصوف'],
        ];

        foreach ($fabrics as $fabric) {
            $posts[] = $this->fabricCare($fabric);
        }

        $posts[] = $this->colorsForSkinTones();
        $posts[] = $this->sizingGuide();
        $posts[] = $this->modestTrends();
        $posts[] = $this->hijabStylingTips();
        $posts[] = $this->buildingAWardrobe();
        $posts[] = $this->giftIdeas();
        $posts[] = $this->behindTheScenes();
        $posts[] = $this->scarfStyling();
        $posts[] = $this->layeringGuide();
        $posts[] = $this->weddingGuestGuide();
        $posts[] = $this->capsuleWardrobe();
        $posts[] = $this->sustainableChoices();
        $posts[] = $this->accessorizingBasics();
        $posts[] = $this->travelPacking();
        $posts[] = $this->officeWearGuide();
        $posts[] = $this->eidStyleGuide();
        $posts[] = $this->ramadanEveningsGuide();
        $posts[] = $this->handbagCare();
        $posts[] = $this->embroideryStory();
        $posts[] = $this->mixingPrints();
        $posts[] = $this->footwearGuide();
        $posts[] = $this->beltStyling();
        $posts[] = $this->jewelryForModestFashion();
        $posts[] = $this->layeringHijabsAndShawls();
        $posts[] = $this->abayaStyleDifferences();
        $posts[] = $this->shoppingSalesSmart();
        $posts[] = $this->leatherCareGuide();

        return collect($posts)->map(function ($post, $i) {
            $post['slug'] = Str::slug($post['title_en']).'-'.($i + 1);

            return $post;
        })->all();
    }

    protected function chooseTheRight(array $g): array
    {
        return [
            'title_en' => "How to Choose the Right {$g['en']}", 'title_ar' => "كيف تختارين {$g['ar']} المناسب",
            'category' => 'Styling Tips',
            'excerpt_en' => "A practical guide to picking a {$g['en']} that fits your body, your budget, and the occasion.",
            'excerpt_ar' => "دليل عملي لاختيار {$g['ar']} الذي يناسب قوامكِ وميزانيتكِ والمناسبة.",
            'body_en' => "Choosing the right {$g['en']} starts with knowing how you'll actually wear it. Think through the occasions you're shopping for first — everyday wear calls for breathable, low-maintenance fabrics, while special occasions can justify richer materials and more detailed embellishment.\n\nFit matters more than trend. A well-cut piece in a simple fabric will always look more elegant than an ill-fitting one in an expensive fabric. Pay attention to shoulder and length first — those are the hardest things to adjust after purchase.\n\nFinally, build around colors you already wear often. A new piece should extend your existing wardrobe, not sit alone in the closet waiting for the one outfit it matches.",
            'body_ar' => "يبدأ اختيار {$g['ar']} المناسب بمعرفة كيف ستستخدمينه فعليًا. فكّري أولًا في المناسبات التي تتسوقين من أجلها — فالارتداء اليومي يحتاج أقمشة خفيفة سهلة العناية، بينما تستحق المناسبات الخاصة خامات أغنى وتفاصيل أكثر دقة.\n\nالمقاس أهم من الموضة. القطعة ذات القصّة الجيدة بخامة بسيطة ستبدو دائمًا أكثر أناقة من قطعة غير مناسبة بخامة غالية. انتبهي أولًا للكتف والطول، فهما أصعب ما يمكن تعديله بعد الشراء.\n\nوأخيرًا، اختاري ألوانًا تتماشى مع خزانة ملابسك الحالية. يجب أن تُكمل القطعة الجديدة إطلالاتك الموجودة، لا أن تبقى معلّقة بانتظار الإطلالة الوحيدة التي تناسبها.",
        ];
    }

    protected function stylingForOccasions(array $g): array
    {
        return [
            'title_en' => "Styling Your {$g['en']} for Special Occasions", 'title_ar' => "طرق تنسيق {$g['ar']} للمناسبات الخاصة",
            'category' => 'Styling Tips',
            'excerpt_en' => "Simple ways to dress up your {$g['en']} for weddings, Eid, and evening events.",
            'excerpt_ar' => "طرق بسيطة لتحويل {$g['ar']} إلى إطلالة مناسبة لحفلات الزفاف والعيد والسهرات.",
            'body_en' => "A single {$g['en']} can carry you through an entire season of events if you know how to shift its accessories. Swap in statement jewelry, a structured bag, and heeled shoes to instantly elevate a piece you already wear often.\n\nLayering is your best tool here — a tailored jacket, an embellished shawl, or a delicate belt can change the entire silhouette without needing a new wardrobe.\n\nKeep makeup and hair a little more polished than usual, and let the {$g['en']} do the rest of the work.",
            'body_ar' => "يمكن لقطعة واحدة من {$g['ar']} أن ترافقك طوال موسم كامل من المناسبات إذا عرفتِ كيف تُغيّرين إكسسواراتها. أضيفي مجوهرات لافتة، حقيبة مُهيكلة، وحذاءً بكعب لترفعي إطلالة معتادة في لحظات.\n\nالتنسيق بالطبقات هو أداتكِ الأفضل هنا — جاكيت مفصّل، أو شال مطرز، أو حزام أنيق يمكنه تغيير الإطلالة بالكامل دون الحاجة لخزانة جديدة.\n\nاجعلي المكياج وتصفيف الشعر أكثر أناقة قليلًا من المعتاد، ودعي {$g['ar']} يكمل الباقي.",
        ];
    }

    protected function seasonalAdvice(array $s): array
    {
        return [
            'title_en' => "Seasonal Fashion Advice: {$s['en']}", 'title_ar' => "نصائح موضة لفصل {$s['ar']}",
            'category' => 'Trends',
            'excerpt_en' => "What to prioritize in your modest wardrobe as {$s['en']} arrives.",
            'excerpt_ar' => "ما الذي يجب التركيز عليه في خزانتكِ المحتشمة مع اقتراب {$s['ar']}.",
            'body_en' => "Every season asks something different of a modest wardrobe. As {$s['en']} arrives, it's worth rotating out pieces that no longer suit the weather and refreshing your rotation with two or three versatile new pieces rather than a full overhaul.\n\nFocus on fabric weight and color palette first — those two choices affect comfort and mood more than any single trend. Layering pieces (a shawl, a light jacket) are the easiest way to stretch the same core wardrobe across changing temperatures.\n\nA seasonal refresh doesn't need to be expensive: often it's simply about knowing which two or three pieces from your existing wardrobe deserve to move to the front of the closet.",
            'body_ar' => "يتطلب كل فصل شيئًا مختلفًا من الخزانة المحتشمة. مع اقتراب {$s['ar']}، يستحق الأمر إعادة ترتيب القطع التي لم تعد تناسب الطقس، وتجديد الخزانة بقطعتين أو ثلاث قطع متعددة الاستخدامات بدلًا من تغيير شامل.\n\nركّزي أولًا على وزن القماش ولوحة الألوان — فهذان الخياران يؤثران على الراحة والمزاج أكثر من أي موضة عابرة. قطع التنسيق بالطبقات (كالشال أو الجاكيت الخفيف) هي أسهل طريقة لتمديد نفس الخزانة الأساسية عبر تغيّرات الطقس.\n\nلا يحتاج التجديد الموسمي إلى ميزانية كبيرة: غالبًا الأمر يتعلق فقط بمعرفة أي قطعتين أو ثلاث من خزانتكِ الحالية تستحق الانتقال إلى مقدمة الخزانة.",
        ];
    }

    protected function fabricCare(array $f): array
    {
        return [
            'title_en' => "Caring for {$f['en']} Fabrics", 'title_ar' => "العناية بالأقمشة {$f['ar']}",
            'category' => 'Fabric Care',
            'excerpt_en' => "How to wash, store, and extend the life of your {$f['en']} pieces.",
            'excerpt_ar' => "كيفية غسل وتخزين قطعكِ {$f['ar']} وإطالة عمرها الافتراضي.",
            'body_en' => "{$f['en']} pieces reward a little extra care with years of extra wear. Always check the care label first, but as a general rule, hand-washing in cool water or using a gentle machine cycle inside a mesh bag protects both color and detail work.\n\nAvoid direct sunlight when drying — lay pieces flat in the shade instead, which prevents fading and keeps embellishments secure. Store finished pieces on padded hangers or folded with tissue paper between layers, away from direct heat or humidity.\n\nA little patience with the right care routine will keep {$f['en']} pieces looking new for years rather than a single season.",
            'body_ar' => "تكافئكِ قطع {$f['ar']} على العناية الإضافية بسنوات أطول من الاستخدام. تحققي دائمًا من ملصق العناية أولًا، لكن كقاعدة عامة، الغسل اليدوي بماء بارد أو الغسيل بدورة لطيفة داخل كيس شبكي يحمي اللون والتفاصيل معًا.\n\nتجنبي أشعة الشمس المباشرة عند التجفيف — افردي القطعة في الظل بدلًا من ذلك، فهذا يمنع بهتان اللون ويحافظ على ثبات الزخارف. خزّني القطع الجاهزة على شماعات مبطنة أو مطوية مع ورق حريري بين الطبقات، بعيدًا عن الحرارة المباشرة والرطوبة.\n\nقليل من الصبر مع روتين العناية الصحيح سيبقي قطع {$f['ar']} تبدو جديدة لسنوات لا لموسم واحد فقط.",
        ];
    }

    protected function colorsForSkinTones(): array
    {
        return [
            'title_en' => 'Choosing Colors for Different Skin Tones', 'title_ar' => 'اختيار الألوان المناسبة لكل لون بشرة',
            'category' => 'Styling Tips',
            'excerpt_en' => 'A simple framework for picking colors that genuinely flatter you.',
            'excerpt_ar' => 'إطار بسيط لاختيار الألوان التي تُبرز جمال بشرتكِ فعليًا.',
            'body_en' => "The easiest way to find flattering colors is to look at the undertone of your skin rather than how light or dark it is. Warm undertones (a golden or peachy cast) tend to glow in colors like camel, olive, and warm rose. Cool undertones (a pink or blue cast) usually suit jewel tones like sapphire, emerald, and true maroon.\n\nIf you're unsure of your undertone, hold a warm gold and a cool silver fabric near your face in natural light — whichever makes your skin look brighter, not paler, is your cue.\n\nNeutrals like black, white, and beige suit almost everyone, which is exactly why they're worth investing in as wardrobe foundations before building out with more personal, flattering accent colors.",
            'body_ar' => "أسهل طريقة لإيجاد الألوان التي تناسبكِ هي النظر إلى درجة بشرتكِ الأساسية بدلًا من فاتحيتها أو غمقانها. البشرة ذات الدرجة الدافئة (المائلة للذهبي أو الخوخي) تتألق مع ألوان مثل الجملي والزيتي والوردي الدافئ. أما البشرة ذات الدرجة الباردة (المائلة للوردي أو الأزرق) فتناسبها عادة الألوان الجوهرية كالياقوتي والزمردي والعنابي الحقيقي.\n\nإذا لم تكوني متأكدة من درجة بشرتكِ، ضعي قماشًا ذهبيًا دافئًا وآخر فضيًا باردًا قرب وجهكِ في ضوء طبيعي — أيهما يجعل بشرتكِ تبدو أكثر إشراقًا لا شحوبًا هو دليلكِ.\n\nالألوان المحايدة كالأسود والأبيض والبيج تناسب الجميع تقريبًا، ولهذا السبب تستحق الاستثمار فيها كأساس للخزانة قبل إضافة ألوان مميزة أكثر شخصية.",
        ];
    }

    protected function sizingGuide(): array
    {
        return [
            'title_en' => 'How to Choose the Correct Size When Shopping Online', 'title_ar' => 'كيف تختارين المقاس الصحيح عند التسوق أونلاين',
            'category' => 'Shopping Guide',
            'excerpt_en' => 'A quick guide to measuring yourself accurately before you order.',
            'excerpt_ar' => 'دليل سريع لأخذ مقاساتكِ بدقة قبل الطلب.',
            'body_en' => "Online sizing gets far more reliable once you have your own three core measurements on hand: bust, waist, and hip, taken with a soft measuring tape over light clothing. Compare these — not your usual size label — against each product's specific size chart, since cuts vary between styles.\n\nWhen you're between two sizes, consider how the piece is meant to fit: flowing abayas and kaftans are more forgiving and usually look better sized up slightly, while tailored dresses benefit from sizing precisely to your waist measurement.\n\nIf you're still unsure, reach out before ordering — a quick message with your measurements is always faster than a return.",
            'body_ar' => "يصبح اختيار المقاس أونلاين أكثر دقة بمجرد أن يكون لديكِ ثلاثة مقاسات أساسية جاهزة: الصدر والخصر والأرداف، مقاسة بشريط قياس ناعم فوق ملابس خفيفة. قارني هذه المقاسات — لا مقاسكِ المعتاد — بجدول المقاسات الخاص بكل منتج، فالقصّات تختلف بين الموديلات.\n\nإذا كنتِ متردّدة بين مقاسين، فكّري في طبيعة القطعة: العبايات والقفاطين الانسيابية أكثر مرونة وعادة ما تبدو أفضل بمقاس أكبر قليلًا، بينما تستفيد الفساتين المفصّلة من الدقة في مقاس الخصر.\n\nإذا بقي لديكِ تردد، تواصلي معنا قبل الطلب — رسالة سريعة بمقاساتكِ أسرع دائمًا من عملية إرجاع.",
        ];
    }

    protected function modestTrends(): array
    {
        return [
            'title_en' => 'Modest Fashion Trends This Season', 'title_ar' => 'أبرز صيحات الموضة المحتشمة هذا الموسم',
            'category' => 'Trends',
            'excerpt_en' => 'The colors, cuts, and details shaping modest fashion right now.',
            'excerpt_ar' => 'الألوان والقصّات والتفاصيل التي تُشكّل الموضة المحتشمة حاليًا.',
            'body_en' => "This season leans toward quiet luxury: rich, solid colors over busy prints, and fabric quality over heavy embellishment. Earth tones — olive, mocha, and warm terracotta — are showing up across abayas and outerwear alike.\n\nStructured shoulders and clean, architectural draping are replacing the softer, looser silhouettes of past seasons, giving even the most relaxed pieces a more considered shape.\n\nAccessories are trending minimal but intentional: a single statement piece — a bold ring or a structured bag — rather than layering several smaller pieces at once.",
            'body_ar' => "يميل هذا الموسم نحو الفخامة الهادئة: الألوان الغنية الموحدة بدلًا من الطبعات المزدحمة، وجودة القماش بدلًا من الزخارف الثقيلة. الألوان الترابية — كالزيتي والموكا والطوبي الدافئ — تظهر في العبايات وقطع الخارجية على حد سواء.\n\nالأكتاف المهيكلة والتدريج المعماري النظيف يحلّان محل الخطوط الأكثر ليونة واتساعًا من المواسم السابقة، مما يمنح حتى أكثر القطع راحة شكلًا أكثر دقة.\n\nتتجه الإكسسوارات نحو البساطة المقصودة: قطعة واحدة لافتة — كخاتم جريء أو حقيبة مُهيكلة — بدلًا من تكديس عدة قطع صغيرة معًا.",
        ];
    }

    protected function hijabStylingTips(): array
    {
        return [
            'title_en' => 'Five Hijab Styling Tips for Everyday Wear', 'title_ar' => 'خمس نصائح لتنسيق الحجاب للارتداء اليومي',
            'category' => 'Styling Tips',
            'excerpt_en' => 'Small adjustments that make your everyday hijab routine faster and more polished.',
            'excerpt_ar' => 'تعديلات بسيطة تجعل روتين الحجاب اليومي أسرع وأكثر أناقة.',
            'body_en' => "Start with the right undercap — a well-fitted, breathable one prevents slipping and keeps the rest of the style in place all day. Choosing a fabric weight suited to the season matters just as much as the style itself: light jersey or chiffon for warm days, thicker weaves for winter.\n\nPin placement is the biggest time-saver: two pins, one under the chin and one at the shoulder, hold most everyday styles securely without needing a dozen adjustments.\n\nFinally, keep two or three go-to styles you can do in under a minute for busy mornings, and save more elaborate draping for occasions when you have the extra time.",
            'body_ar' => "ابدئي بالطاقية الداخلية المناسبة — طاقية مريحة قابلة للتنفس تمنع الانزلاق وتُبقي باقي التنسيق ثابتًا طوال اليوم. اختيار وزن قماش يناسب الفصل مهم بقدر أهمية الستايل نفسه: الجيرسيه أو الشيفون الخفيف للأيام الدافئة، والأقمشة الأكثر سماكة للشتاء.\n\nموضع الدبابيس هو أكبر موفّر للوقت: دبوسان، واحد تحت الذقن وآخر عند الكتف، يثبّتان معظم الإطلالات اليومية دون الحاجة لعشرات التعديلات.\n\nأخيرًا، احتفظي بستايلين أو ثلاثة يمكن تنفيذها في أقل من دقيقة للصباحات المزدحمة، ووفّري التدريج الأكثر تفصيلًا للمناسبات التي يتوفر فيها وقت إضافي.",
        ];
    }

    protected function buildingAWardrobe(): array
    {
        return [
            'title_en' => 'Building an Elegant, Versatile Wardrobe', 'title_ar' => 'كيف تبنين خزانة أنيقة ومتعددة الاستخدامات',
            'category' => 'Wardrobe',
            'excerpt_en' => 'How to build a wardrobe where every piece works with several others.',
            'excerpt_ar' => 'كيف تبنين خزانة كل قطعة فيها تتناسق مع عدة قطع أخرى.',
            'body_en' => "An elegant wardrobe isn't about owning more — it's about owning pieces that combine easily. Start with a small set of neutral, well-tailored basics (a classic abaya, a structured bag, a versatile scarf) before adding statement pieces on top.\n\nBefore buying anything new, ask whether it works with at least two pieces you already own. If the answer is no, it's likely to sit unused no matter how appealing it looks in the store.\n\nInvest the largest part of your budget in the pieces you'll wear most often — everyday abayas and hijabs — and save splurges for the occasion pieces that genuinely need them.",
            'body_ar' => "الخزانة الأنيقة لا تعني امتلاك المزيد، بل امتلاك قطع تتناسق بسهولة. ابدئي بمجموعة صغيرة من الأساسيات المحايدة جيدة التفصيل (عباية كلاسيكية، حقيبة مُهيكلة، إيشارب متعدد الاستخدامات) قبل إضافة القطع اللافتة فوقها.\n\nقبل شراء أي قطعة جديدة، اسألي نفسكِ إن كانت تتناسق مع قطعتين على الأقل مما تملكينه بالفعل. إذا كانت الإجابة لا، فمن المرجح أن تبقى معلّقة دون استخدام مهما بدت جذابة في المتجر.\n\nخصّصي الجزء الأكبر من ميزانيتكِ للقطع التي سترتدينها أكثر — العبايات والحجابات اليومية — واحتفظي بالإنفاق الأكبر للقطع المخصصة للمناسبات التي تستحقه فعلًا.",
        ];
    }

    protected function giftIdeas(): array
    {
        return [
            'title_en' => 'Gift Ideas from Dar El-Jamila', 'title_ar' => 'أفكار هدايا من دار الجميلة',
            'category' => 'Gift Guide',
            'excerpt_en' => 'Thoughtful gift ideas for every budget and occasion.',
            'excerpt_ar' => 'أفكار هدايا مدروسة تناسب كل ميزانية ومناسبة.',
            'body_en' => "A beautifully packaged scarf or hijab set is one of the easiest gifts to get right — it suits nearly any taste and rarely needs an exact size. For a more personal gift, a piece from a favorite color family shows you've paid attention to what she already wears.\n\nFor milestone occasions, a statement abaya or an evening piece makes a memorable gift that she'll likely wear for years rather than a single event.\n\nWhen size is uncertain, jewelry, bags, and accessories are the safest route — elegant, thoughtful, and free of the guesswork that comes with garment sizing.",
            'body_ar' => "الإيشارب أو طقم الحجاب المُغلّف بعناية من أسهل الهدايا التي يمكن اختيارها بثقة — فهو يناسب معظم الأذواق ونادرًا ما يحتاج مقاسًا دقيقًا. أما للهدية الأكثر شخصية، فقطعة من لون تحبه المُهداة إليها تُظهر أنكِ انتبهتِ لما تحب ارتداءه فعلًا.\n\nللمناسبات المهمة، تُعد العباية اللافتة أو قطعة السهرة هدية لا تُنسى، ومن المرجح أن تُرتدى لسنوات لا لمناسبة واحدة فقط.\n\nعند عدم التأكد من المقاس، تُعد المجوهرات والحقائب والإكسسوارات الخيار الأكثر أمانًا — أنيقة ومدروسة وخالية من عدم اليقين المرتبط بمقاسات الملابس.",
        ];
    }

    protected function behindTheScenes(): array
    {
        return [
            'title_en' => 'Behind the Scenes of a Dar El-Jamila Collection', 'title_ar' => 'خلف كواليس مجموعة من دار الجميلة',
            'category' => 'Our Story',
            'excerpt_en' => 'A look at how a collection comes together, from fabric selection to final fitting.',
            'excerpt_ar' => 'نظرة على كيفية تجهيز مجموعة كاملة، من اختيار القماش إلى التفصيل النهائي.',
            'body_en' => "Every collection begins months before it reaches the store, with fabric sourcing trips and countless swatches compared side by side under different lighting. Only a fraction of what's considered ever makes it to final selection.\n\nFrom there, each silhouette goes through several fitting rounds on real models before a single detail — an embroidery pattern, a button, a lining color — is finalized.\n\nThe last stage is always the most demanding: quality checks on every seam and stitch, because a collection is only as good as its least visible detail.",
            'body_ar' => "تبدأ كل مجموعة قبل وصولها إلى المتجر بأشهر، برحلات اختيار الأقمشة ومقارنة عشرات العيّنات جنبًا إلى جنب تحت إضاءات مختلفة. لا يصل إلى الاختيار النهائي سوى جزء صغير مما تم النظر فيه.\n\nمن هناك، يمر كل تصميم بعدة جولات تفصيل على عارضات حقيقيات قبل اعتماد أي تفصيلة نهائية — نقشة تطريز، زر، أو لون بطانة.\n\nالمرحلة الأخيرة هي الأصعب دائمًا: فحص الجودة لكل غرزة وخيط، لأن المجموعة لا تُقاس إلا بأدق تفاصيلها غير الظاهرة.",
        ];
    }

    protected function scarfStyling(): array
    {
        return [
            'title_en' => 'Five Ways to Style a Silk Scarf', 'title_ar' => 'خمس طرق لتنسيق الإيشارب الحريري',
            'category' => 'Styling Tips',
            'excerpt_en' => 'From neckwear to hair accessory — five ways to wear one scarf.',
            'excerpt_ar' => 'من ربطة الرقبة إلى إكسسوار الشعر — خمس طرق لارتداء إيشارب واحد.',
            'body_en' => "A single silk scarf is one of the most versatile pieces in a wardrobe. Tied loosely around the neck, it adds a pop of color to a plain outfit in seconds. Folded into a thin band, it doubles as an elegant hair accessory or headband.\n\nDraped over the shoulders, it works as a light layer for cooler evenings, and knotted onto a bag handle, it instantly refreshes an older accessory.\n\nKeep two or three scarves in colors that complement your most-worn pieces, and you'll find new ways to style them long after the first outfit.",
            'body_ar' => "الإيشارب الحريري الواحد من أكثر القطع تعدد استخدامات في الخزانة. مربوطًا بخفة حول الرقبة، يضيف لمسة لونية لإطلالة بسيطة في ثوانٍ. مطويًا إلى شريط رفيع، يتحول إلى إكسسوار شعر أنيق أو رباط رأس.\n\nملقىً على الكتفين، يعمل كطبقة خفيفة للأمسيات الباردة، ومعقودًا على مقبض حقيبة، يُجدّد إكسسوارًا قديمًا في لحظة.\n\nاحتفظي بإيشاربين أو ثلاثة بألوان تكمّل قطعكِ الأكثر ارتداءً، وستجدين طرقًا جديدة لتنسيقها طويلًا بعد الإطلالة الأولى.",
        ];
    }

    protected function layeringGuide(): array
    {
        return [
            'title_en' => 'The Art of Layering Modest Fashion', 'title_ar' => 'فن التنسيق بالطبقات في الموضة المحتشمة',
            'category' => 'Styling Tips',
            'excerpt_en' => 'How to layer without adding bulk or losing your silhouette.',
            'excerpt_ar' => 'كيف تُنسّقين بالطبقات دون إضافة حجم زائد أو فقدان القوام.',
            'body_en' => "Good layering starts with fabric weight, not just color. Pair lighter, closer-fitting base pieces with looser outer layers — the contrast keeps the whole look balanced rather than bulky.\n\nLength is your next consideration: an outer layer that's noticeably shorter or longer than the piece underneath creates visual interest, while matching lengths can flatten the silhouette.\n\nStick to two or three tones per outfit at most — one neutral base, one accent color in the layer, and perhaps one metallic or textured accessory to finish.",
            'body_ar' => "يبدأ التنسيق الجيد بالطبقات بوزن القماش لا باللون فقط. اقرني القطع الأساسية الأخف والأقرب للجسم بطبقات خارجية أوسع — فالتباين يُبقي الإطلالة متوازنة بدلًا من أن تبدو ثقيلة.\n\nالطول هو الاعتبار التالي: طبقة خارجية أقصر أو أطول بوضوح من القطعة التي تحتها تخلق إثارة بصرية، بينما تتساوي الأطوال قد يُسطّح القوام.\n\nالتزمي بدرجتين أو ثلاث درجات لونية كحد أقصى لكل إطلالة — أساس محايد، ولون مميز في الطبقة، وربما إكسسوار معدني أو ذو ملمس مميز لإكمال الإطلالة.",
        ];
    }

    protected function weddingGuestGuide(): array
    {
        return [
            'title_en' => 'A Modest Wedding Guest Style Guide', 'title_ar' => 'دليل الإطلالة المحتشمة لضيفة حفل الزفاف',
            'category' => 'Occasion Guides',
            'excerpt_en' => 'What to wear as a guest without outshining — or under-dressing for — the celebration.',
            'excerpt_ar' => 'ماذا ترتدين كضيفة دون أن تتفوقي على العروس أو تقلّلي من أناقة المناسبة.',
            'body_en' => "The safest wedding-guest rule is to match the formality of the invitation, then add one standout element — a rich color, a statement accessory, or fine embellishment — rather than maximizing all three at once.\n\nAvoid pure white or overly bridal silhouettes out of respect for the bride, but rich jewel tones and metallics are almost always welcome at evening celebrations.\n\nComfort matters more than it seems: weddings run long, so choose a fabric and fit you can move, sit, and dance in for several hours without adjusting all night.",
            'body_ar' => "القاعدة الأكثر أمانًا كضيفة في حفل زفاف هي مطابقة درجة الرسمية المذكورة في الدعوة، ثم إضافة عنصر واحد لافت — لون غني، إكسسوار مميز، أو زخرفة راقية — بدلًا من المبالغة في الثلاثة معًا.\n\nتجنبي اللون الأبيض الخالص أو القصّات الشبيهة بفستان العروس احترامًا للعروس، لكن الألوان الجوهرية الغنية والمعدنية مُرحّب بها غالبًا في احتفالات المساء.\n\nالراحة أهم مما يبدو: حفلات الزفاف تمتد لساعات طويلة، فاختاري خامة وقصّة يمكنكِ الحركة والجلوس والرقص بها لساعات دون الحاجة للتعديل طوال الليل.",
        ];
    }

    protected function capsuleWardrobe(): array
    {
        return [
            'title_en' => 'Capsule Wardrobe Essentials for Modest Dressing', 'title_ar' => 'أساسيات الخزانة المصغّرة للإطلالة المحتشمة',
            'category' => 'Wardrobe',
            'excerpt_en' => 'A short list of pieces that can carry an entire season on their own.',
            'excerpt_ar' => 'قائمة مختصرة بالقطع القادرة على تغطية موسم كامل بمفردها.',
            'body_en' => "A true capsule wardrobe rests on a handful of pieces that all share a compatible color story: one everyday abaya, two or three hijabs in neutral tones, a versatile scarf, a structured bag, and comfortable everyday shoes.\n\nFrom there, one or two statement pieces — an embroidered kaftan or an evening dress — cover the occasions the everyday pieces can't.\n\nThe goal isn't owning less for its own sake; it's owning fewer, better pieces that combine into more outfits than a much larger, less coordinated wardrobe ever could.",
            'body_ar' => "تقوم الخزانة المصغّرة الحقيقية على عدد قليل من القطع التي تتشارك قصة لونية متناسقة: عباية يومية واحدة، حجابان أو ثلاثة بدرجات محايدة، إيشارب متعدد الاستخدامات، حقيبة مُهيكلة، وحذاء يومي مريح.\n\nمن هناك، تُغطي قطعة أو قطعتان لافتتان — قفطان مطرز أو فستان سهرة — المناسبات التي لا تكفيها القطع اليومية.\n\nالهدف ليس امتلاك أقل لمجرد الأقل، بل امتلاك قطع أقل وأفضل تتناسق لتُنتج إطلالات أكثر مما قد تنتجه خزانة أكبر بكثير لكنها أقل تناسقًا.",
        ];
    }

    protected function sustainableChoices(): array
    {
        return [
            'title_en' => 'Sustainable, Timeless Fashion Choices', 'title_ar' => 'خيارات موضة مستدامة وخالدة',
            'category' => 'Our Story',
            'excerpt_en' => 'Why investing in fewer, better pieces is the most sustainable choice you can make.',
            'excerpt_ar' => 'لماذا يُعد الاستثمار في قطع أقل وأفضل هو الخيار الأكثر استدامة.',
            'body_en' => "The most sustainable piece in any wardrobe is the one you'll still wear in five years. Choosing timeless cuts and quality fabric over fast trends means fewer replacements and less waste over time.\n\nCare matters just as much as the initial purchase — a well-maintained piece can easily outlast several cheaply made alternatives, which is ultimately better value as well as better for the planet.\n\nBuying fewer, more considered pieces isn't a limitation; it's the foundation of a wardrobe that actually reflects your taste rather than whatever trend was current the week you shopped.",
            'body_ar' => "أكثر قطعة مستدامة في أي خزانة هي تلك التي ستستمرين في ارتدائها بعد خمس سنوات. اختيار القصّات الخالدة والأقمشة عالية الجودة بدلًا من صيحات سريعة الزوال يعني استبدالًا أقل وهدرًا أقل مع الوقت.\n\nالعناية لا تقل أهمية عن الشراء نفسه — القطعة جيدة الصيانة يمكنها بسهولة أن تدوم أطول من عدة بدائل رخيصة الصنع، وهذا في النهاية قيمة أفضل وأثر أفضل على البيئة أيضًا.\n\nشراء قطع أقل وأكثر دراسة ليس قيدًا، بل هو أساس خزانة تعكس ذوقكِ الحقيقي بدلًا من أي صيحة كانت رائجة في أسبوع الشراء.",
        ];
    }

    protected function accessorizingBasics(): array
    {
        return [
            'title_en' => 'Accessorizing Basics: Less Is Often More', 'title_ar' => 'أساسيات الإكسسوارات: الأقل غالبًا أفضل',
            'category' => 'Styling Tips',
            'excerpt_en' => 'A simple rule for choosing accessories that elevate rather than overwhelm.',
            'excerpt_ar' => 'قاعدة بسيطة لاختيار إكسسوارات تُبرز الإطلالة لا أن تُثقلها.',
            'body_en' => "Before adding an accessory, look at your outfit as a whole and choose one focal point — a bag, a necklace, or earrings — rather than styling all three at their full statement volume.\n\nMetal tones should stay consistent across a single outfit where possible; mixing gold and silver can work, but it takes a more practiced eye than most everyday looks need.\n\nWhen in doubt, remove one accessory before you leave the house — a slightly simpler look almost always reads as more intentional than an overstyled one.",
            'body_ar' => "قبل إضافة أي إكسسوار، انظري إلى إطلالتكِ ككل واختاري نقطة تركيز واحدة — حقيبة، قلادة، أو أقراط — بدلًا من تنسيق الثلاثة معًا بأقصى حضور لكل منها.\n\nيُفضّل أن تبقى درجات المعدن متناسقة في الإطلالة الواحدة قدر الإمكان؛ مزج الذهبي والفضي يمكن أن ينجح، لكنه يحتاج عينًا أكثر خبرة مما تحتاجه معظم الإطلالات اليومية.\n\nعند التردد، أزيلي إكسسوارًا واحدًا قبل الخروج — الإطلالة الأبسط قليلًا تبدو دائمًا أكثر قصدية من إطلالة مُثقلة بالإكسسوارات.",
        ];
    }

    protected function travelPacking(): array
    {
        return [
            'title_en' => 'Packing a Modest Travel Wardrobe', 'title_ar' => 'تحضير خزانة سفر محتشمة',
            'category' => 'Wardrobe',
            'excerpt_en' => 'How to pack light without sacrificing outfit options on a trip.',
            'excerpt_ar' => 'كيف تُسافرين بأمتعة خفيفة دون التضحية بخيارات إطلالتكِ.',
            'body_en' => "Plan a travel wardrobe around one color story so every piece can pair with every other piece — this alone can cut your packing list in half without cutting your outfit options.\n\nPrioritize wrinkle-resistant fabrics like jersey and crepe over delicate silks for the pieces you'll wear most during transit and daily sightseeing, saving one special piece for an evening out.\n\nRoll rather than fold to save space, and pack one versatile scarf that can double as a light layer, a headscarf, or even an emergency accessory if luggage runs tight.",
            'body_ar' => "خطّطي لخزانة السفر حول قصة لونية واحدة بحيث تتناسق كل قطعة مع الأخرى — هذا وحده يمكن أن يُقلّل قائمة أمتعتكِ للنصف دون تقليل خيارات إطلالتكِ.\n\nأعطي الأولوية للأقمشة المقاومة للتجعد كالجيرسيه والكريب بدلًا من الحرير الحساس للقطع التي سترتدينها أكثر أثناء التنقل والتجول اليومي، واحتفظي بقطعة مميزة واحدة لسهرة خاصة.\n\nلفّي الملابس بدلًا من طيّها لتوفير المساحة، واصطحبي إيشاربًا متعدد الاستخدامات يمكنه أن يكون طبقة خفيفة، أو غطاء رأس، أو حتى إكسسوارًا طارئًا إذا ضاقت الأمتعة.",
        ];
    }

    protected function officeWearGuide(): array
    {
        return [
            'title_en' => 'A Modest Office Wardrobe That Works', 'title_ar' => 'خزانة عمل محتشمة وعملية',
            'category' => 'Occasion Guides',
            'excerpt_en' => 'Building a professional wardrobe around comfort and quiet elegance.',
            'excerpt_ar' => 'بناء خزانة عمل احترافية تجمع بين الراحة والأناقة الهادئة.',
            'body_en' => "Office dressing rewards structure: a tailored abaya or a coordinated set in a neutral tone reads as effortlessly professional and pairs easily with different hijab colors day to day.\n\nKeep fabric choices practical for a full workday — breathable, low-wrinkle materials that hold their shape through long hours at a desk or in meetings.\n\nOne or two quality bags in versatile colors will outlast several trend-driven pieces, and are worth the larger investment in a work wardrobe built to last.",
            'body_ar' => "تنجح إطلالة العمل مع القصّات المهيكلة: عباية مفصّلة أو طقم منسّق بدرجة محايدة يمنح إطلالة احترافية دون عناء، ويتناسق بسهولة مع ألوان حجاب مختلفة يوميًا.\n\nاختاري أقمشة عملية تناسب يوم عمل كامل — خامات قابلة للتنفس ومقاومة للتجعد تحافظ على شكلها طوال ساعات العمل الطويلة أو الاجتماعات.\n\nحقيبة أو حقيبتان عالية الجودة بألوان متعددة الاستخدامات ستدومان أطول من عدة قطع تتبع صيحات عابرة، وتستحقان استثمارًا أكبر في خزانة عمل مصممة لتدوم.",
        ];
    }

    protected function eidStyleGuide(): array
    {
        return [
            'title_en' => 'Eid Style Guide: Celebratory Looks That Last', 'title_ar' => 'دليل إطلالات العيد التي تدوم',
            'category' => 'Occasion Guides',
            'excerpt_en' => 'Choosing Eid pieces you\'ll want to wear again long after the holiday.',
            'excerpt_ar' => 'اختيار قطع عيد ترغبين في إعادة ارتدائها طويلًا بعد انتهاء العيد.',
            'body_en' => "Eid calls for celebration, but the most rewarding pieces are the ones versatile enough to wear again at other gatherings throughout the year — think rich color and fine detailing over anything too costume-like.\n\nCoordinate rather than match exactly with family members: complementary tones photograph beautifully together without requiring identical outfits.\n\nA well-chosen kaftan or embellished abaya, paired with the right accessories, can anchor your Eid look for several years running rather than a single celebration.",
            'body_ar' => "يستدعي العيد إطلالة احتفالية، لكن أكثر القطع تستحق الاستثمار هي تلك المرنة بما يكفي لإعادة ارتدائها في مناسبات أخرى خلال العام — فكّري في الألوان الغنية والتفاصيل الراقية بدلًا من أي شيء أقرب للزي التنكري.\n\nنسّقي الألوان مع أفراد العائلة بدلًا من المطابقة التامة: الدرجات المتكاملة تظهر بشكل جميل معًا في الصور دون الحاجة لإطلالات متطابقة.\n\nقفطان أو عباية مطرزة مُختارة بعناية، مع الإكسسوارات المناسبة، يمكنها أن تكون ركيزة إطلالة عيدكِ لعدة سنوات متتالية لا لاحتفال واحد فقط.",
        ];
    }

    protected function ramadanEveningsGuide(): array
    {
        return [
            'title_en' => 'Dressing for Ramadan Evenings and Iftar Gatherings', 'title_ar' => 'إطلالات أمسيات رمضان وموائد الإفطار',
            'category' => 'Occasion Guides',
            'excerpt_en' => 'Comfortable, elegant pieces for a month of gatherings and long evenings.',
            'excerpt_ar' => 'قطع مريحة وأنيقة لشهر مليء بالتجمعات والأمسيات الطويلة.',
            'body_en' => "Ramadan evenings call for pieces that stay comfortable through long gatherings at the table while still feeling a little more elevated than everyday wear — soft, flowing fabrics in richer tones strike that balance well.\n\nA versatile jalabiya or kaftan you can dress up with jewelry for iftar and down with flats for suhoor earns its place in the wardrobe many times over during the month.\n\nKeep one or two pieces in reserve for the final ten nights and Eid itself, so your most special pieces still feel fresh when the month's biggest celebrations arrive.",
            'body_ar' => "تتطلب أمسيات رمضان قطعًا تبقى مريحة خلال التجمعات الطويلة حول المائدة، مع الاحتفاظ بإحساس أرقى قليلًا من الإطلالة اليومية — الأقمشة الناعمة الانسيابية بدرجات أغنى تحقق هذا التوازن جيدًا.\n\nجلابية أو قفطان متعدد الاستخدامات يمكن تنسيقه بالمجوهرات للإفطار وبحذاء مسطح للسحور يستحق مكانه في الخزانة مرات عديدة خلال الشهر.\n\nاحتفظي بقطعة أو قطعتين للعشر الأواخر والعيد نفسه، حتى تبقى أكثر قطعكِ تميّزًا بإحساس جديد عندما تصل أكبر احتفالات الشهر.",
        ];
    }

    protected function handbagCare(): array
    {
        return [
            'title_en' => 'How to Care for Your Handbag', 'title_ar' => 'كيفية العناية بحقيبتكِ',
            'category' => 'Fabric Care',
            'excerpt_en' => 'Simple habits that keep a good bag looking new for years.',
            'excerpt_ar' => 'عادات بسيطة تُبقي حقيبتكِ الجيدة تبدو جديدة لسنوات.',
            'body_en' => "A good bag deserves a few simple habits: store it stuffed with tissue paper to hold its shape, and keep it in a dust bag away from direct sunlight when not in use.\n\nWipe leather down with a soft, dry cloth after regular use, and treat any spill immediately rather than letting it set — leather and suede both stain more easily once a mark has dried in.\n\nRotate between two or three bags rather than using one daily; giving leather time to rest between uses noticeably extends its life.",
            'body_ar' => "تستحق الحقيبة الجيدة بضع عادات بسيطة: احشيها بورق حريري للحفاظ على شكلها، واحتفظي بها داخل كيس قماشي بعيدًا عن أشعة الشمس المباشرة عند عدم الاستخدام.\n\nامسحي الجلد بقطعة قماش ناعمة وجافة بعد الاستخدام المعتاد، وعالجي أي بقعة فورًا بدلًا من تركها، فكل من الجلد والسويدي يتبقعان بسهولة أكبر بعد جفاف الأثر.\n\nناوبي بين حقيبتين أو ثلاث بدلًا من استخدام واحدة يوميًا؛ فمنح الجلد وقتًا للراحة بين الاستخدامات يُطيل عمره بشكل ملحوظ.",
        ];
    }

    protected function embroideryStory(): array
    {
        return [
            'title_en' => 'The Story Behind Our Embroidery', 'title_ar' => 'قصة التطريز في قطعنا',
            'category' => 'Our Story',
            'excerpt_en' => 'A look at the handwork behind every embroidered piece.',
            'excerpt_ar' => 'نظرة على العمل اليدوي خلف كل قطعة مطرزة.',
            'body_en' => "Every embroidered pattern starts as a hand-sketched design, refined over several drafts before a single thread is stitched. Skilled embroiderers then work the pattern by hand or on a fine machine, a process that can take anywhere from several hours to several days depending on the intricacy.\n\nThread color and stitch density are chosen to catch the light differently depending on the base fabric, which is why the same pattern can look entirely different on silk versus crepe.\n\nThe result is a detail meant to be noticed slowly — up close, in changing light, rather than all at once.",
            'body_ar' => "يبدأ كل نقش تطريز كرسمة يدوية، تُصقل عبر عدة مسودات قبل أن يُغرز فيها أول خيط. يعمل الحرفيون المهرة بعدها على تنفيذ النقشة يدويًا أو على ماكينة دقيقة، وهي عملية قد تستغرق من عدة ساعات إلى عدة أيام حسب دقة التفاصيل.\n\nيُختار لون الخيط وكثافة الغرز ليعكسا الضوء بشكل مختلف حسب القماش الأساسي، ولهذا يمكن للنقشة ذاتها أن تبدو مختلفة تمامًا على الحرير مقارنة بالكريب.\n\nالنتيجة تفصيلة يُقصد بها أن تُلاحَظ ببطء — عن قرب، وفي إضاءة متغيرة، لا دفعة واحدة.",
        ];
    }

    protected function mixingPrints(): array
    {
        return [
            'title_en' => 'Mixing Prints and Patterns Modestly', 'title_ar' => 'مزج الطبعات والنقوش بإطلالة محتشمة',
            'category' => 'Styling Tips',
            'excerpt_en' => 'A confident approach to combining prints without clashing.',
            'excerpt_ar' => 'طريقة واثقة لمزج النقوش دون تنافر.',
            'body_en' => "Mixing prints works best when the patterns share a common color rather than competing palettes — a floral scarf and a geometric bag can work together if they share even one matching tone.\n\nVary the scale: pair a small, busy print with a larger, simpler one so the eye has a clear place to rest between them.\n\nWhen in doubt, let one printed piece lead the outfit and keep everything else solid — it's the easiest way to experiment with pattern without the risk of visual clutter.",
            'body_ar' => "يعمل مزج النقوش بشكل أفضل عندما تتشارك النقشات لونًا مشتركًا بدلًا من لوحات متنافسة — إيشارب بنقشة زهور وحقيبة بنقشة هندسية يمكن أن يتناسقا إذا تشاركا درجة لونية واحدة على الأقل.\n\nنوّعي حجم النقشة: اقرني نقشة صغيرة مزدحمة بأخرى أكبر وأبسط حتى تجد العين مكانًا واضحًا للراحة بينهما.\n\nعند التردد، اجعلي قطعة واحدة منقوشة تقود الإطلالة واحتفظي بباقي القطع سادة — إنها أسهل طريقة لتجربة النقوش دون خطر التشتت البصري.",
        ];
    }

    protected function footwearGuide(): array
    {
        return [
            'title_en' => 'A Footwear Guide for Modest Outfits', 'title_ar' => 'دليل الأحذية المناسبة للإطلالة المحتشمة',
            'category' => 'Styling Tips',
            'excerpt_en' => 'Choosing shoes that complete a modest outfit rather than compete with it.',
            'excerpt_ar' => 'اختيار حذاء يُكمل الإطلالة المحتشمة لا أن ينافسها.',
            'body_en' => "With longer hemlines, shoes carry more visual weight than in shorter outfits — a clean, well-chosen pair can anchor an entire look. Neutral tones (black, nude, or a tone matching the outfit) keep the focus on the silhouette as a whole.\n\nFor everyday wear, prioritize comfort first: a flowing abaya or jalabiya pairs just as well with flats or low block heels as with anything taller.\n\nSave statement shoes — bold color, embellishment, unusual texture — for occasions where the hemline is shorter or the outfit is otherwise kept simple, so the shoes have room to stand out.",
            'body_ar' => "مع الأطوال الأطول، يحمل الحذاء وزنًا بصريًا أكبر مما في الإطلالات الأقصر — فزوج أنيق ومختار بعناية يمكنه أن يكون ركيزة الإطلالة كاملة. الدرجات المحايدة (الأسود، البيج، أو درجة تطابق الإطلالة) تُبقي التركيز على القوام ككل.\n\nللارتداء اليومي، أعطي الأولوية للراحة أولًا: العباية أو الجلابية الانسيابية تتناسق جيدًا مع الحذاء المسطح أو الكعب المنخفض بقدر تناسقها مع أي كعب أعلى.\n\nاحتفظي بالأحذية اللافتة — اللون الجريء، الزخرفة، الملمس غير المعتاد — للمناسبات التي يكون فيها الطول أقصر أو الإطلالة أبسط، حتى يحظى الحذاء بمساحة ليبرز.",
        ];
    }

    protected function beltStyling(): array
    {
        return [
            'title_en' => 'How to Style a Statement Belt', 'title_ar' => 'كيف تُنسّقين حزامًا لافتًا',
            'category' => 'Styling Tips',
            'excerpt_en' => 'Using a belt to add shape to flowing silhouettes.',
            'excerpt_ar' => 'استخدام الحزام لإضافة قوام لإطلالة انسيابية.',
            'body_en' => "A belt is one of the fastest ways to add shape to a flowing abaya or kaftan — worn loosely at the natural waist, it defines the silhouette without needing a different, more fitted piece.\n\nWide belts suit heavier fabrics like wool-blend coats, while thinner, more delicate belts work better over lighter crepe and chiffon.\n\nFor an evening look, a metallic or embellished belt can double as the outfit's main accessory, meaning you can keep jewelry minimal elsewhere.",
            'body_ar' => "الحزام من أسرع الطرق لإضافة قوام للعباية أو القفطان الانسيابي — مربوطًا بخفة عند الخصر الطبيعي، فهو يُحدد القوام دون الحاجة لقطعة مختلفة أكثر تفصيلًا.\n\nتناسب الأحزمة العريضة الأقمشة الأثقل كمعاطف الصوف، بينما تعمل الأحزمة الأرق والأكثر رقة بشكل أفضل فوق الكريب والشيفون الأخف.\n\nللإطلالة المسائية، يمكن لحزام معدني أو مزخرف أن يكون الإكسسوار الرئيسي للإطلالة، مما يعني إمكانية إبقاء باقي المجوهرات بسيطة.",
        ];
    }

    protected function jewelryForModestFashion(): array
    {
        return [
            'title_en' => 'Choosing Jewelry for Modest Fashion', 'title_ar' => 'اختيار المجوهرات المناسبة للموضة المحتشمة',
            'category' => 'Styling Tips',
            'excerpt_en' => 'How jewelry choices shift when a hijab changes what\'s visible.',
            'excerpt_ar' => 'كيف يتغير اختيار المجوهرات عندما يُغيّر الحجاب ما هو ظاهر.',
            'body_en' => "With a hijab framing the face, earrings and necklaces read differently than they would otherwise — drop earrings and shorter necklaces tend to show best, since they sit closer to visible skin.\n\nRings and bracelets carry more visual weight in modest dressing generally, since hands are often one of the few areas left fully uncovered — a statement ring can be a whole outfit's accent piece on its own.\n\nAs with any accessory, choose one focal piece rather than layering several strong pieces at once, and let it complement your outfit's dominant color rather than competing with it.",
            'body_ar' => "مع تأطير الحجاب للوجه، تظهر الأقراط والقلادات بشكل مختلف عما لو لم تكن — فالأقراط المتدلية والقلادات القصيرة تظهر بشكل أفضل غالبًا، لأنها تقع قرب الجلد الظاهر.\n\nتحمل الخواتم والأساور وزنًا بصريًا أكبر عمومًا في الإطلالة المحتشمة، إذ تبقى اليدان من المناطق القليلة الظاهرة بالكامل — فخاتم لافت يمكنه أن يكون لمسة الإطلالة كاملة بمفرده.\n\nكما مع أي إكسسوار، اختاري قطعة تركيز واحدة بدلًا من تكديس عدة قطع قوية معًا، ودعيها تُكمّل اللون السائد في إطلالتكِ بدلًا من منافسته.",
        ];
    }

    protected function layeringHijabsAndShawls(): array
    {
        return [
            'title_en' => 'A Guide to Layering Hijabs and Shawls', 'title_ar' => 'دليل التنسيق بين الحجاب والشال',
            'category' => 'Styling Tips',
            'excerpt_en' => 'Combining a hijab and shawl for extra coverage and elegance.',
            'excerpt_ar' => 'الجمع بين الحجاب والشال لتغطية إضافية وإطلالة أنيقة.',
            'body_en' => "Layering a shawl over a hijab is one of the easiest ways to add both warmth and elegance without changing your base style. Choose a shawl in a lighter or complementary tone to your hijab so the layers read as intentional rather than accidental.\n\nDrape it loosely over one shoulder for a relaxed daytime look, or wrap it fully for extra coverage and warmth on colder days.\n\nA textured or embellished shawl can also double as an occasion accessory — thrown over a simple hijab, it instantly elevates an everyday outfit for an evening event.",
            'body_ar' => "طبقة شال فوق الحجاب من أسهل الطرق لإضافة دفء وأناقة دون تغيير أسلوبكِ الأساسي. اختاري شالًا بدرجة أفتح أو مكمّلة لحجابكِ حتى تبدو الطبقات مقصودة لا عشوائية.\n\nألقيه بخفة على كتف واحد لإطلالة نهارية مريحة، أو لفّيه بالكامل لتغطية ودفء إضافيين في الأيام الأكثر برودة.\n\nيمكن لشال ذي ملمس مميز أو مزخرف أن يكون إكسسوار مناسبة أيضًا — ملقىً فوق حجاب بسيط، يرفع إطلالة يومية فورًا لتناسب سهرة مسائية.",
        ];
    }

    protected function abayaStyleDifferences(): array
    {
        return [
            'title_en' => 'Understanding the Difference Between Abaya Styles', 'title_ar' => 'الفرق بين أنماط العبايات المختلفة',
            'category' => 'Shopping Guide',
            'excerpt_en' => 'Butterfly, closed, and kimono cuts explained simply.',
            'excerpt_ar' => 'شرح مبسّط لقصّات الفراشة والمغلقة والكيمونو.',
            'body_en' => "The closed abaya — a single seamless piece worn over the head or zipped at the front — offers the most streamlined silhouette and works well for both everyday wear and formal occasions.\n\nThe butterfly abaya, cut wider and open at the front, drapes more loosely over the body and is often chosen for warmer weather or a more relaxed fit.\n\nThe kimono-style abaya borrows its wide, straight sleeves from Japanese dress, giving a more structured, contemporary silhouette that pairs particularly well with a fitted underlayer.",
            'body_ar' => "العباية المغلقة — قطعة واحدة متصلة تُرتدى من فوق الرأس أو بسحاب أمامي — تمنح القوام الأكثر انسيابية وتناسب الارتداء اليومي والمناسبات الرسمية معًا.\n\nعباية الفراشة، ذات القصّة الأوسع والمفتوحة من الأمام، تنسدل بشكل أكثر ارتخاءً على الجسم وغالبًا ما تُختار للطقس الأدفأ أو لإطلالة أكثر راحة.\n\nعباية الكيمونو تستعير أكمامها العريضة المستقيمة من الزي الياباني، مما يمنح قوامًا أكثر تهيكلًا وعصرية، وتتناسق بشكل خاص مع طبقة داخلية مفصّلة.",
        ];
    }

    protected function shoppingSalesSmart(): array
    {
        return [
            'title_en' => 'How to Shop Smart During Sales', 'title_ar' => 'كيف تتسوقين بذكاء خلال التخفيضات',
            'category' => 'Shopping Guide',
            'excerpt_en' => 'Getting real value from a sale instead of impulse purchases.',
            'excerpt_ar' => 'الحصول على قيمة حقيقية من التخفيضات بدلًا من الشراء الاندفاعي.',
            'body_en' => "Before a sale begins, make a short list of pieces you were already considering — shopping with a plan makes it far easier to spot genuine value instead of buying simply because something is discounted.\n\nPrioritize investment pieces you'll wear often (an everyday abaya, a versatile bag) over occasion pieces during a sale, since the everyday pieces earn back their cost many times over through regular wear.\n\nAlways check the size chart and return policy on sale items just as carefully as full-price ones — a discount isn't a good deal if the piece doesn't end up fitting or suiting you.",
            'body_ar' => "قبل بدء التخفيضات، جهّزي قائمة قصيرة بالقطع التي كنتِ تفكرين فيها بالفعل — فالتسوق بخطة يجعل من الأسهل بكثير ملاحظة القيمة الحقيقية بدلًا من الشراء لمجرد وجود خصم.\n\nأعطي الأولوية للقطع الاستثمارية التي سترتدينها كثيرًا (عباية يومية، حقيبة متعددة الاستخدامات) على قطع المناسبات خلال التخفيضات، فالقطع اليومية تُعيد قيمتها مرات عديدة عبر الاستخدام المتكرر.\n\nتحققي دائمًا من جدول المقاسات وسياسة الإرجاع لقطع التخفيضات بنفس دقة القطع كاملة السعر — فالخصم ليس صفقة جيدة إذا لم تناسبكِ القطعة في النهاية.",
        ];
    }

    protected function leatherCareGuide(): array
    {
        return [
            'title_en' => 'Caring for Leather Bags and Shoes', 'title_ar' => 'العناية بالحقائب والأحذية الجلدية',
            'category' => 'Fabric Care',
            'excerpt_en' => 'Keeping leather pieces supple and looking new.',
            'excerpt_ar' => 'الحفاظ على مرونة القطع الجلدية وبقائها بمظهر جديد.',
            'body_en' => "Leather needs to breathe and stay slightly moisturized to avoid cracking over time. A leather conditioner applied every few months keeps the material supple, especially in drier climates or heavily air-conditioned spaces.\n\nKeep leather away from direct heat sources and prolonged sun exposure, both of which dry it out and can cause discoloration.\n\nFor suede specifically, a dedicated suede brush and immediate attention to any spill will prevent the permanent marks that plain wiping can't fix once they've set.",
            'body_ar' => "يحتاج الجلد إلى التنفس والحفاظ على ترطيب خفيف لتجنب التشقق مع الوقت. استخدام مرطب جلد كل بضعة أشهر يُبقي الخامة مرنة، خاصة في الأجواء الجافة أو الأماكن ذات التكييف القوي.\n\nأبعدي الجلد عن مصادر الحرارة المباشرة والتعرض الطويل للشمس، فكلاهما يُجفف الخامة وقد يسبب تغيّر اللون.\n\nبالنسبة للسويدي تحديدًا، فإن فرشاة سويدي مخصصة والاهتمام الفوري بأي بقعة يمنعان الآثار الدائمة التي لا يمكن للمسح العادي إزالتها بعد أن تجف.",
        ];
    }
}
