"use client";

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from "framer-motion";
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Flame, Clock, TrendingUp, Star } from 'lucide-react';
import { cn } from "@/lib/utils";

interface SortSelectorProps {
  currentSort: string;
  onSortChange: (value: string) => void;
}

export default function SortSelector({ currentSort, onSortChange }: SortSelectorProps) {
  const [mounted, setMounted] = useState(false);
  const [localSort, setLocalSort] = useState(currentSort);

  useEffect(() => {
    setMounted(true);
    return () => setMounted(false);
  }, []);

  const handleSortChange = (value: string) => {
    setLocalSort(value);
    onSortChange(value);
  };

  const sortOptions = [
    { value: 'hot', label: '🔥人気', icon: Flame },
    { value: 'new', label: '🆕最新', icon: Clock },
    { value: 'top', label: '⭐殿堂', icon: Star },
    { value: 'rising', label: '📈上昇中', icon: TrendingUp },
  ];

  return (
    <div className="w-full">
      <div className="relative inline-flex h-8 w-full rounded-lg bg-input/50 p-0.5">
        <RadioGroup
          value={localSort}
          onValueChange={handleSortChange}
          className="relative z-10 inline-grid w-full grid-cols-4 items-center gap-0 text-sm font-medium"
        >
          {sortOptions.map((option) => (
            <label
              key={option.value}
              className={cn(
                "relative z-10 inline-flex h-7 min-w-8 cursor-pointer select-none items-center justify-center whitespace-nowrap px-2",
                "transition-colors duration-300",
                "text-muted-foreground/70",
                localSort === option.value ? "text-foreground" : "hover:text-muted-foreground"
              )}
            >
              <span className="flex items-center gap-1">
                {option.label}
              </span>
              <RadioGroupItem
                id={`sort-${option.value}`}
                value={option.value}
                className="sr-only"
              />
              {localSort === option.value && (
                <motion.div
                  layoutId="sort-indicator"
                  className="absolute inset-0 -z-10 rounded-md bg-background/80"
                  initial={false}
                  animate={{ opacity: 1 }}
                  transition={{
                    type: "spring",
                    stiffness: 400,
                    damping: 30,
                    mass: 0.8
                  }}
                >
                  <motion.div
                    initial={false}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.2 }}
                    className="absolute -top-[1px] left-1/2 -translate-x-1/2 w-8 h-[2px] bg-primary rounded-t-full"
                  >
                    <div className="absolute w-12 h-6 bg-primary/20 rounded-full blur-md -top-2 -left-2" />
                    <div className="absolute w-8 h-6 bg-primary/20 rounded-full blur-md -top-1" />
                    <div className="absolute w-4 h-4 bg-primary/20 rounded-full blur-sm top-0 left-2" />
                  </motion.div>
                  <div className="absolute inset-0 shadow-[0_0_6px_rgba(0,0,0,0.03),0_2px_6px_rgba(0,0,0,0.08),inset_3px_3px_0.5px_-3px_rgba(0,0,0,0.9),inset_-3px_-3px_0.5px_-3px_rgba(0,0,0,0.85),inset_1px_1px_1px_-0.5px_rgba(0,0,0,0.6),inset_-1px_-1px_1px_-0.5px_rgba(0,0,0,0.6),inset_0_0_6px_6px_rgba(0,0,0,0.12),inset_0_0_2px_2px_rgba(0,0,0,0.06),0_0_12px_rgba(255,255,255,0.15)] dark:shadow-[0_0_8px_rgba(0,0,0,0.03),0_2px_6px_rgba(0,0,0,0.08),inset_3px_3px_0.5px_-3.5px_rgba(255,255,255,0.09),inset_-3px_-3px_0.5px_-3.5px_rgba(255,255,255,0.85),inset_1px_1px_1px_-0.5px_rgba(255,255,255,0.6),inset_-1px_-1px_1px_-0.5px_rgba(255,255,255,0.6),inset_0_0_6px_6px_rgba(255,255,255,0.12),inset_0_0_2px_2px_rgba(255,255,255,0.06),0_0_12px_rgba(0,0,0,0.15)]" />
                </motion.div>
              )}
            </label>
          ))}
        </RadioGroup>
      </div>
    </div>
  );
} 