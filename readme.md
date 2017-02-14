# CMS pro Nette Framework
Administrace webové aplikace

## Nastaveni
Zaregistrujte a nastavete extension v **config.neon**. Od namespace je odvozen název modulu pro další rozšíření administrace a to tak, že se k namespace přidá '*Ext*'
```neon
extensions:
    cms: NAttreid\Cms\DI\CmsExtension

cms:
    namespace: 'Cms'
    url: '/cms/'
    sender: 'Odesilatel <nejaky@mail.cz>'
    front: ':Front:Homepage:'

    fileManagerDir: %appDir%/../
    infoRefresh: 15 # vteriny

    minPasswordLength: 8
    passwordChars: '0-9a-zA-Z'

    loginExpiration: '20 minutes'
    sessionExpiration: '14 days'
    
    tracy:
        cookie: nejakyHash

    layout: '%appDir%/cms/templates/cms.latte' # hlavní šablona
```

a přidejte model do ORM. V příkladu je extension orm pod nazvem **orm**
```neon
orm:
    add:
        - NAttreid\Cms\Model\Orm
```

Pro přidání *assets* použijte
```neon
cms:
    assets:
        - %wwwDir%/js/example.js
        - %wwwDir%/css/example.css
        - {%wwwDir%/js/cs.js, locale: cs} # localizace pro cs
        - http://someUrt/scritp.js # remote
        - //someUrt/scritp.js # remote
        - {%wwwDir%/js/example.js, remote: true} # remote
        - {files: ["*.js", "*.css", "*.less"], from: %appDir%/cms}
```

Přídání dalších modulů
```neon
cms:
    menu:
        Example:
            link: 'Homepage:'
            web:
                test:
                    link: 'action' # pokud je null, provede se default action
```
Presenter musí dědit z třídy **\NAttreid\Cms\Control\ModulePresenter**. Příklad presenteru test z ukázky menu
```php
namespace App\Cms\Example\Presenters;

class TestPresenter extends \NAttreid\Cms\Control\ModulePresenter {
    public function renderDefault() {
        // pro zobrazeni menu v mobilu (defaultne je skryto)
        $this->viewMobileMenu();
    }
}
```

**cms.latte**
```latte
{extends $layout}
```
Šablona **@layout.latte** pro modul musí dědit z *cms.latte*


## Rozšiřitelnost pomocí extension
Třída extension musí dědit z **\NAttreid\Cms\DI\ModuleExtension**
```php
class ExampleExtension extends \NAttreid\Cms\DI\ModuleExtension {

    protected $namespace = 'example';
    protected $dir = __DIR__;
    protected $package = 'Package\\';

    public function beforeCompile() {
        parent::beforeCompile();
        $this->addLoaderFile('cestaKCssNeboJs');
        $this->addLoaderFile('cestaKLocalizovanemuJs','cs');
    }

}
```

a v složce musí být soubor **default.neon**
```neon
link: 'Homepage:'
position: 1

menu:
    group:
        test:
            link:
```

Presenter musí dědit z třídy **\NAttreid\Cms\Control\ModulePresenter**
```php
namespace Package\Example\Presenters;

class TestPresenter extends \NAttreid\Cms\Control\ModulePresenter {
    
}
```



