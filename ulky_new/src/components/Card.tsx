import React from 'react';
import { View, ViewProps, Text } from 'react-native';
import { colors, shadows, spacing, typography } from '@/theme';

interface CardProps extends ViewProps {
  children: React.ReactNode;
  variant?: 'default' | 'elevated';
}

export function Card({ children, variant = 'default', style, ...rest }: CardProps) {
  const variantStyles = {
    default: {
      backgroundColor: colors.background.default,
      borderWidth: 1,
      borderColor: colors.border.light,
    },
    elevated: {
      backgroundColor: colors.background.default,
      ...shadows.subtle,
    },
  };

  return (
    <View
      style={[
        {
          borderRadius: 12,
          padding: spacing.lg,
          gap: spacing.md,
          ...variantStyles[variant],
        },
        style,
      ]}
      {...rest}
    >
      {children}
    </View>
  );
}

interface BadgeProps {
  label: string;
  variant?: 'primary' | 'success' | 'warning' | 'error';
}

export function Badge({ label, variant = 'primary' }: BadgeProps) {
  const variantStyles = {
    primary: {
      backgroundColor: `${colors.primary[600]}20`, // 20% opacity
      borderColor: colors.primary[600],
    },
    success: {
      backgroundColor: `${colors.success[600]}20`,
      borderColor: colors.success[600],
    },
    warning: {
      backgroundColor: `${colors.warning[600]}20`,
      borderColor: colors.warning[600],
    },
    error: {
      backgroundColor: `${colors.error[600]}20`,
      borderColor: colors.error[600],
    },
  };

  const textColor = {
    primary: colors.primary[600],
    success: colors.success[600],
    warning: colors.warning[600],
    error: colors.error[600],
  };

  return (
    <View
      style={{
        borderRadius: 20,
        paddingHorizontal: spacing.md,
        paddingVertical: spacing.xs,
        borderWidth: 1,
        ...variantStyles[variant],
      }}
    >
      <Text
        style={{
          ...typography.caption,
          color: textColor[variant],
          textAlign: 'center',
        }}
      >
        {label}
      </Text>
    </View>
  );
}
