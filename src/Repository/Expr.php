<?php

namespace SamFreeze\SymfonyCrudBundle\Repository;

class Expr {
    // none filter
    const none = 'none';
    // Example - $qb->expr()->eq('u.id', '?1') => u.id = ?1
    const eq = 'eq';
    // Example - $qb->expr()->neq('u.id', '?1') => u.id <> ?1
    const neq = 'neq';
    // Example - $qb->expr()->lt('u.id', '?1') => u.id < ?1
    const lt = 'lt';
    // Example - $qb->expr()->lte('u.id', '?1') => u.id <= ?1
    const lte = 'lte';
    // Example - $qb->expr()->gt('u.id', '?1') => u.id > ?1
    const gt = 'gt';
    // Example - $qb->expr()->gte('u.id', '?1') => u.id >= ?1
    const gte = 'gte';
    // Example - $qb->expr()->isNull('u.id') => u.id IS NULL
    const isNull = 'isNull';
    // Example - $qb->expr()->isNotNull('u.id') => u.id IS NOT NULL
    const isNotNull = 'isNotNull';

    // Example - $qb->expr()->in('u.id', array(1, 2, 3))
    // Make sure that you do NOT use something similar to $qb->expr()->in('value', array('stringvalue')) as this will cause Doctrine to throw an Exception.
    // Instead, use $qb->expr()->in('value', array('?1')) and bind your parameter to ?1 (see section above)
    const in = 'in';
    // Example - $qb->expr()->notIn('u.id', '2')
    const notIn = 'notIn';
    // Example - $qb->expr()->like('u.firstname', $qb->expr()->literal('Gui%'))
    const like = 'like';
    // Example - $qb->expr()->notLike('u.firstname', $qb->expr()->literal('Gui%'))
    const notLike = 'notLike';

}