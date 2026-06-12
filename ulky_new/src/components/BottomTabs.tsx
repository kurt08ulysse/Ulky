import React from 'react';
import { View, Pressable, Text, useSafeAreaInsets } from 'react-native';
import { colors, spacing, typography } from '@/theme';

export interface BottomTabItem {
  name: string;
  label: string;
  icon: React.ReactNode;
  onPress: () => void;
  active: boolean;
}

interface BottomTabsProps {
  items: BottomTabItem[];
}

export function BottomTabs({ items }: BottomTabsProps) {
  const insets = useSafeAreaInsets();

  return (
    <View
      style={{
        flexDirection: 'row',
        height: 56 + insets.bottom,
        borderTopWidth: 1,
        borderTopColor: colors.border.light,
        backgroundColor: colors.background.default,
        paddingBottom: insets.bottom,
      }}
    >
      {items.map((item, index) => (
        <Pressable
          key={item.name}
          onPress={item.onPress}
          style={{
            flex: 1,
            justifyContent: 'center',
            alignItems: 'center',
            gap: spacing.xs,
            opacity: item.active ? 1 : 0.6,
          }}
        >
          <View style={{ height: 24, width: 24 }}>
            {React.cloneElement(item.icon as React.ReactElement, {
              color: item.active ? colors.primary[600] : colors.text.tertiary,
              size: 24,
            })}
          </View>
          <Text
            style={{
              ...typography.caption,
              color: item.active ? colors.primary[600] : colors.text.tertiary,
            }}
          >
            {item.label}
          </Text>
        </Pressable>
      ))}
    </View>
  );
}
