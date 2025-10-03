<?php
declare(strict_types=1);

/*
 * This file is part of the Contao Flex Bundle Extension - Extension of tdoescher/flex-bundle.
 *
 * (c) www.green-pixelbox.de
 *
 * @license LGPL-3.0-or-later
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Database;

// Tut die neuen Felder nur laden, wenn Flexbox (Bootstrap) gewählt ist
$GLOBALS['TL_DCA']['tl_content']['fields']['flex_bootstrap']['default'] = '1';
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['tl_content_flex', 'showNewFields'];
$GLOBALS['TL_DCA']['tl_content']['fields']['flex_xs']['eval']['tl_class'] = 'w50 clr';

// Neues Preset-Feld zum Setzen der Spalten per Klick
$GLOBALS['TL_DCA']['tl_content']['fields']['gp_flex_preset'] = [
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['2', '3', '4', '6', 'a', '1'],
    'reference' => [
        '1' => 'alle Spalten in eine Zeile (Werte leeren)',
        '2' => '2-spaltig',
        '3' => '3-spaltig',
        '4' => '4-spaltig',
        '6' => '6-spaltig',
        'a' => 'automatisch',
    ],
    'eval'      => [
        'includeBlankOption' => true,
        'chosen'             => false,
        'tl_class'           => 'w50',
        'submitOnChange'     => true
    ],
    'save_callback' => [
        ['tl_content_flex', 'applyFlexPreset']
    ],
    'sql' => "varchar(1) NOT NULL default ''"
];


// Feld für JPG-Hilfe-Modal (öffnet Bild im Iframe-Modal)
$GLOBALS['TL_DCA']['tl_content']['fields']['gp_overview_flex_layout'] = [
    'input_field_callback' => array('tl_content_flex', 'getLayoutColmns'),
    'eval'      => [
        'tl_class'           => 'w50 clr'
    ],
];




/**
 * Callback-Klasse für DCA-Logik
 */
class tl_content_flex
{
    /**
     * Setzt flex_xs und flex_md anhand des gewählten Presets.
     *
     * @param string $value Gewähltes Preset ('', '1', '2', '3')
     * @param \Contao\DataContainer|null $dc
     * @return string
     */
    public function applyFlexPreset($value, $dc)
    {
        // Nur ausführen, wenn es sich um einen submitOnChange-Reload (AJAX/Widget-Reload) handelt
        // und nicht beim normalen "Speichern".
        if (
            !isset($_POST['REQUEST_TOKEN']) // minimaler Schutz, kein regulärer Form-Submit ohne Token
            || empty($_POST['SUBMIT_TYPE']) // Contao setzt bei onchange-Reload typischerweise SUBMIT_TYPE / FORM_SUBMIT Marker
            || ($_POST['SUBMIT_TYPE'] ?? '') !== 'auto' // 'auto' = submitOnChange; normale Saves sind leer/anders
        ) {
            return $value;
        }

        if (!$dc || !$dc->activeRecord || !$value) {
            return $value;
        }

        // Mapping der Presets zu gewünschten Werten
        // Hinweis: Bei Bedarf anpassen
        $map = [
            '1' => ['xs' => '', 'sm' => '', 'md' => '', 'lg' => '', 'xl' => '', 'xxl' => ''],
            '2' => ['xs' => '12', 'sm' => '6', 'md' => '', 'lg' => '', 'xl' => '', 'xxl' => ''],
            '3' => ['xs' => '12', 'sm' => '', 'md' => '6', 'lg' => '4', 'xl' => '', 'xxl' => ''],
            '4' => ['xs' => '12', 'sm' => '6', 'md' => '6', 'lg' => '4', 'xl' => '3', 'xxl' => ''],
            '6' => ['xs' => '12', 'sm' => '6', 'md' => '4', 'lg' => '3', 'xl' => '2', 'xxl' => ''],
            'a' => ['xs' => 'a', 'sm' => '', 'md' => '', 'lg' => '', 'xl' => '', 'xxl' => ''],
        ];


        if (!isset($map[$value])) {
            return $value;
        }

        $xs = $map[$value]['xs'];
        $sm = $map[$value]['sm'];
        $md = $map[$value]['md'];
        $lg = $map[$value]['lg'];
        $xl = $map[$value]['xl'];
        $xxl = $map[$value]['xxl'];

        // In aktiven Datensatz schreiben
        $dc->activeRecord->flex_xs = $xs;
        $dc->activeRecord->flex_sm = $sm;
        $dc->activeRecord->flex_md = $md;
        $dc->activeRecord->flex_lg = $lg;
        $dc->activeRecord->flex_xl = $xl;
        $dc->activeRecord->flex_xxl = $xxl;

        // Persistieren
        Database::getInstance()
            ->prepare("UPDATE tl_content SET flex_xs=?, flex_sm=?, flex_md=?, flex_lg=?, flex_xl=?, flex_xxl=? WHERE id=?")
            ->execute($xs, $sm, $md, $lg, $xl, $xxl, $dc->activeRecord->id);

        return $value;
    }

    /**
     * Zeigt den Link zur Vorschau des Spalten Layouts von Flexbox.
     * @param string $value
     * @param \Contao\DataContainer|null $dc
     */
    public function getLayoutColmns($value, $dc)
    {
        $imgUrl = '/bundles/gpflexbundleextension/overview-colmns.png'; // absolute URL zur PNG
        $title = 'Layout 12‑Spalten Grid';
        $linktext = 'Anzeigen';

        $returnValue = '<div class="cbx w50 widget"><h3>'.$title.'</h3><p>'
            . '<a href="' . htmlspecialchars($imgUrl) . '" '
            . 'onclick="Backend.openModalIframe({\'title\':\'' . htmlspecialchars($title, ENT_QUOTES) . '\',\'url\':this.href});return false">'
            . htmlspecialchars($linktext)
            . '</a></p></div>';

        return $returnValue;
    }

    /**
     * Fügt Felder nur ein, wenn flex_bootstrap == '1'
     */
    public function showNewFields($dc): void
    {
        // Datensatz laden
        $row = Database::getInstance()
            ->prepare('SELECT id, type, flex_bootstrap FROM tl_content WHERE id=?')
            ->limit(1)
            ->execute($dc->id)
            ->fetchAssoc();

        if (!$row || $row['flex_bootstrap'] !== '1') {
            return;
        }

        // Felder gezielt einfügen
        PaletteManipulator::create()
            ->addField('gp_flex_preset', 'flex_xs', PaletteManipulator::POSITION_BEFORE)
            ->applyToPalette('flex', 'tl_content');

        PaletteManipulator::create()
            ->addField('gp_overview_flex_layout', 'flex_xxl', PaletteManipulator::POSITION_AFTER)
            ->applyToPalette('flex', 'tl_content');
    }


}
