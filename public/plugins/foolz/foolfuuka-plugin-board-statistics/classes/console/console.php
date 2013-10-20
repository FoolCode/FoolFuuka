<?php

namespace Foolz\Foolfuuka\Plugins\BoardStatistics\Console;

use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolfuuka\Model\RadixCollection;
use Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics as BS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends Command
{
    /**
     * @var \Foolz\Foolframe\Model\Context
     */
    protected $context;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var BS
     */
    protected $board_stats;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->dc = $context->getService('doctrine');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
        $this->board_stats = $context->getService('foolfuuka-plugin.board_statistics');
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('board_statistics:run')
            ->setDescription('Runs the database queries to feed the board statistics in an endless loop')
            ->addOption(
                'radix',
                null,
                InputOption::VALUE_OPTIONAL,
                _i('Run the queries only for the requested board')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($radix = $input->getOption('radix')) !== null) {
            if ($this->radix_coll->getByShortname($radix) !== false) {
                $this->board_statistics($output, $radix);
            } else {
                $output->writeln('<error>'._i('Wrong radix (board short name) specified.').'</error>');
            }
        } else {
            $this->board_statistics($output);
        }
    }

    public function board_statistics($output, $shortname = null)
    {
        $boards = $this->radix_coll->getAll();

        $available = $this->board_stats->getAvailableStats();

        while(true) {
            // Obtain all of the statistics already stored on the database to check for update frequency.
            $stats = $this->dc->qb()
                ->select('board_id, name, timestamp')
                ->from($this->dc->p('plugin_fu_board_statistics'), 'bs')
                ->orderBy('timestamp', 'desc')
                ->execute()
                ->fetchAll();

            // Obtain the list of all statistics enabled.
            $avail = [];
            foreach ($available as $k => $a) {
                // get only the non-realtime ones
                if (isset($available['frequency'])) {
                    $avail[] = $k;
                }
            }

            foreach ($boards as $board) {
                if (!is_null($shortname) && $shortname != $board->shortname) {
                    continue;
                }

                // Update all statistics for the specified board or current board.
                $output->writeln($board->shortname . ' (' . $board->id . ')');
                foreach ($available as $k => $a) {
                    $output->writeln('  ' . $k . ' ');
                    $found = false;
                    $skip = false;

                    foreach ($stats as $r) {
                        // Determine if the statistics already exists or that the information is outdated.
                        if ($r['board_id'] == $board->id && $r['name'] == $k) {
                            // This statistics report has run once already.
                            $found = true;

                            if (!isset($a['frequency'])) {
                                $skip = true;
                                continue;
                            }

                            // This statistics report has not reached its frequency EOL.
                            if (time() - $r['timestamp'] <= $a['frequency']) {
                                $skip = true;
                                continue;
                            }
                            break;
                        }
                    }

                    // racing conditions with our cron.
                    if ($found === false) {
                       $this->board_stats->saveStat($board->id, $k, time() + 600, '');
                    }

                    // We were able to obtain a LOCK on the statistics report and has already reached the
                    // targeted frequency time.
                    if ($skip === false) {
                        $output->writeln('* Processing...');

                        $process = 'process'.$a['function'];
                        $result = $this->board_stats->$process($board);

                        // Save the statistics report in a JSON array.
                        $this->board_stats->saveStat($board->id, $k, time(), $result);
                    }
                }
            }

            sleep(10);
        }
    }
}
