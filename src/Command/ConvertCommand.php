<?php

namespace Wusuopu\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command.
 */
class ConvertCommand extends Command
{
    protected $file_type;
    protected function configure()
    {
        $this->file_type = array(
            'csv' => 'Csv',
            'icudat' => 'IcuDat',
            'icures' => 'IcuRes',
            'ini' => 'Ini',
            'json' => 'Json',
            'mo' => 'Mo',
            'php' => 'Php',
            'po' => 'Po',
            'qt' => 'Qt',
            'xliff' => 'Xliff',
            'yaml' => 'Yaml',
        );
        $this->setName('convert')->setDescription('convert translation file in php.')
             ->addArgument('input', InputArgument::REQUIRED, 'The input file which will be converted.')
             ->addArgument('output', InputArgument::OPTIONAL, 'The output file which will store result.')
             ->addOption('itype', 'i', InputOption::VALUE_REQUIRED, 'The file type of input. The type only can choose from csv, icudat, icures, ini, json, mo,  php, po, qt, xliff and yaml.')
             ->addOption('otype', 'o', InputOption::VALUE_REQUIRED, 'The file type of output. The type only can choose from csv, icudat, icures, ini, json, mo,  php, po, qt, xliff and yaml.')
             ->addOption('locale', 'l', InputOption::VALUE_REQUIRED, 'Current locale name.', 'en');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input_type = $input->getOption('itype');
        $output_type = $input->getOption('otype');
        $locale = $input->getOption('locale');

        if (!array_key_exists($input_type, $this->file_type)) {
            $output->writeln('<error>The input file type only can choose from csv, icudat, icures, ini, json, mo,  php, po, qt, xliff and yaml.</error>');
            return;
        }
        if (!array_key_exists($output_type, $this->file_type)) {
            $output->writeln('<error>The output file type only can choose from csv, icudat, icures, ini, json, mo,  php, po, qt, xliff and yaml.</error>');
            return;
        }
        if ($input_type === $output_type) {
            $output->writeln('<error>The output file type can\'t be same as input file type. </error>');
            return;
        }

        $input_file = $input->getArgument('input');
        $output_file = $input->getArgument('output');

        if (!file_exists($input_file)) {
            $output->writeln("<error>file '$input_file' is not exists.</error>");
            return;
        }
        if (!$output_file) {
            $input_info = pathinfo($input_file);
            $output_file = sprintf(
                "%s/%s.%s", $input_info['dirname'], $input_info['filename'], $output_type);
        }

        $loader_class = 'Symfony\\Component\\Translation\\Loader\\' . $this->file_type[$input_type] . 'FileLoader';
        $loader = new $loader_class();
        $msg = $loader->load($input_file, $locale);

        $outpath = sys_get_temp_dir() . '/php-translate-convert-' . time();
        if (!file_exists($outpath)) {
            mkdir($outpath, 0777, true);
        }

        $dumperr_class = 'Symfony\\Component\\Translation\\Dumper\\' . $this->file_type[$output_type] . 'FileDumper';
        $dumper = new $dumperr_class();
        $dumper->dump($msg, array('path' => $outpath));
        // backup the old file.
        if (file_exists($output_file)) {
            $date = new \DateTime();
            rename($output_file, $output_file . '.' . $date->format('Ymd_His'));
        }
        rename(sprintf('%s/messages.%s.%s', $outpath, $locale, $output_type), $output_file);
        rmdir($outpath);

        $output->writeln("<info>convert '$input_file' to '$output_file'</info>");
    }
}
